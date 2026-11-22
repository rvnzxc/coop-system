<?php

namespace App\Services;

use App\Http\Controllers\AnalyticsController;
use App\Models\Category;
use App\Models\Credit;
use App\Models\Item;
use App\Models\Member;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportService
{
    public const TYPES = ['credits', 'sales', 'inventory', 'member-purchases', 'transactions'];

    public const PERIODS = ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'];

    public const STATUSES = ['all', 'paid', 'partial', 'unpaid', 'overdue'];

    public function validate(Request $request, string $type): array
    {
        if (! in_array($type, self::TYPES, true)) {
            abort(404);
        }

        $validated = $request->validate([
            'format' => ['required', 'in:pdf,csv'],
            'scope' => ['nullable', 'in:period,range'],
            'period' => ['nullable', 'in:'.implode(',', self::PERIODS)],
            'from' => ['nullable', 'date_format:Y-m-d', 'required_with:to'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from', 'required_with:from'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
            'year' => ['nullable', 'digits:4', 'integer', 'min:2000', 'max:2100'],
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['nullable', 'in:'.implode(',', self::STATUSES)],
            'payment_method' => ['nullable', 'in:all,cash,credit'],
        ]);

        $filters = array_merge([
            'scope' => 'period',
            'period' => 'daily',
            'status' => 'all',
            'payment_method' => 'all',
        ], $validated);

        if ($filters['scope'] === 'range' && (empty($filters['from']) || empty($filters['to']))) {
            throw ValidationException::withMessages([
                'from' => 'Select both a start date and an end date for a custom range.',
            ]);
        }

        return $filters;
    }

    public function build(string $type, array $filters, ?int $cashierId = null): array
    {
        return match ($type) {
            'credits' => $this->creditDefinition($filters, $cashierId),
            'sales' => $cashierId !== null
                ? $this->cashierSalesDefinition($filters, $cashierId)
                : $this->salesDefinition($filters),
            'inventory' => $this->inventoryDefinition($filters, $cashierId),
            'member-purchases' => $this->memberPurchasesDefinition($filters, $cashierId),
            'transactions' => $this->transactionsDefinition($filters, $cashierId),
        };
    }

    private function creditDefinition(array $filters, ?int $cashierId = null): array
    {
        [$start, $end] = $this->scopeRange($filters);
        $memberId = $filters['member_id'] ?? null;
        $statusAllowed = in_array($filters['status'], ['all', 'unpaid'], true);

        $sales = Sale::query()
            ->where('payment_method', 'credit')
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereBetween('created_at', [$start, $end])
            ->when($memberId, fn ($query) => $query->where('member_id', $memberId))
            ->when($cashierId, fn ($query) => $query->where('cashier_id', $cashierId))
            ->get(['id', 'member_id', 'cashier_id', 'created_at']);

        $creditKeys = $sales
            ->map(fn (Sale $sale) => $sale->member_id.'|'.$sale->created_at->format('Y-m-d H:i:s'))
            ->unique()
            ->values();

        $base = function () use ($start, $end, $memberId, $creditKeys, $cashierId) {
            return Credit::query()
                ->when($memberId, fn ($query) => $query->where('member_id', $memberId))
                ->whereBetween('created_at', [$start, $end])
                ->when($cashierId, function ($query) use ($creditKeys) {
                    if ($creditKeys->isEmpty()) {
                        $query->whereRaw('1 = 0');

                        return;
                    }

                    $query->where(function ($query) use ($creditKeys) {
                        foreach ($creditKeys as $key) {
                            [$saleMemberId, $reference] = explode('|', $key, 2);
                            $query->orWhere(function ($query) use ($saleMemberId, $reference) {
                                $query->where('member_id', $saleMemberId)
                                    ->where('sale_reference', $reference);
                            });
                        }
                    });
                });
        };

        $totalCredit = $statusAllowed ? round((float) (clone $base())->sum('amount'), 2) : 0.0;
        $membersWithBalance = 0;
        if ($statusAllowed) {
            $membersWithBalance = (int) (clone $base())
                ->distinct()
                ->count('member_id');
        }

        $cashierNames = User::whereIn('id', $sales->pluck('cashier_id')->filter()->unique())
            ->pluck('name', 'id');

        $cashierByReference = $sales->keyBy(function (Sale $sale) {
            return $sale->member_id.'|'.$sale->created_at->format('Y-m-d H:i:s');
        });

        $columns = [
            'Member ID', 'Name', 'Total Credit Taken', 'Total Paid',
            'Outstanding Balance', 'Last Credit Date', 'Status',
        ];

        $writeRows = function (callable $emit) use ($base, $statusAllowed) {
            if (! $statusAllowed) {
                $emit($this->emptyRow(count($this->creditColumns())));

                return;
            }

            $grouped = [];
            (clone $base())->with('member')->orderBy('id')->chunk(500, function ($credits) use (&$grouped) {
                foreach ($credits as $credit) {
                    $id = (int) $credit->member_id;
                    if (! isset($grouped[$id])) {
                        $grouped[$id] = [
                            'number' => $credit->member?->member_number ?: '#'.$id,
                            'name' => trim(($credit->member?->first_name ?? '').' '.($credit->member?->last_name ?? '')) ?: 'Unknown member',
                            'total' => 0.0,
                            'count' => 0,
                            'last' => null,
                        ];
                    }
                    $grouped[$id]['total'] += (float) $credit->amount;
                    $grouped[$id]['count']++;
                    $created = $credit->created_at?->copy()->setTimezone('Asia/Manila');
                    if ($created && (! $grouped[$id]['last'] || $created->greaterThan($grouped[$id]['last']))) {
                        $grouped[$id]['last'] = $created;
                    }
                }
            });

            if ($grouped === []) {
                $emit($this->emptyRow(count($this->creditColumns())));

                return;
            }

            foreach ($grouped as $group) {
                $emit($this->row([
                    $group['number'],
                    $group['name'],
                    round($group['total'], 2),
                    0.00,
                    round($group['total'], 2),
                    $group['last']?->format('M d, Y g:i A') ?: '—',
                    'Unpaid',
                ]));
            }
        };

        $sections = [];
        if ($statusAllowed) {
            $sections[] = [
                'title' => 'Individual credit transactions',
                'columns' => ['Date', 'Receipt / Transaction No.', 'Items', 'Amount', 'Cashier'],
                'currencyColumns' => [3],
                'writeRows' => function (callable $emit) use ($base, $cashierByReference, $cashierNames) {
                    $found = false;
                    (clone $base())->with('member')->orderBy('id')->chunk(500, function ($credits) use ($emit, $cashierByReference, $cashierNames, &$found) {
                        foreach ($credits as $credit) {
                            $found = true;
                            $key = $credit->member_id.'|'.(string) $credit->sale_reference;
                            $sale = $cashierByReference->get($key);
                            $cashierId = $sale?->cashier_id;
                            $items = collect($credit->items_snapshot ?? [])
                                ->map(fn ($item) => ($item['product_name'] ?? 'Item').' x'.($item['quantity'] ?? 1))
                                ->implode(', ');

                            $emit($this->row([
                                $credit->created_at?->copy()->setTimezone('Asia/Manila')->format('M d, Y g:i A') ?: '—',
                                $credit->sale_reference ?: '#'.$credit->id,
                                $items !== '' ? $items : '—',
                                (float) $credit->amount,
                                $cashierId && isset($cashierNames[$cashierId]) ? $cashierNames[$cashierId] : '—',
                            ]));
                        }
                    });

                    if (! $found) {
                        $emit($this->emptyRow(5));
                    }
                },
            ];
        }

        return [
            'meta' => $this->meta($cashierId === null ? 'credits' : 'my-credits', $cashierId === null ? 'Credits Report' : 'My Credits Report', $filters, [], $cashierId),
            'summary' => [
                ['label' => 'Total credit sales', 'value' => $this->peso($totalCredit)],
                ['label' => 'Total repayments', 'value' => $this->peso(0)],
                ['label' => 'Outstanding balance', 'value' => $this->peso($totalCredit)],
                ['label' => 'Members with balance', 'value' => (string) $membersWithBalance],
            ],
            'note' => 'No repayment or due-date records exist. All credit sales are treated as outstanding; Paid, Partial and Overdue cannot be derived.',
            'columns' => $columns,
            'currencyColumns' => [2, 3, 4],
            'writeRows' => $writeRows,
            'totals' => ['TOTAL', '', $totalCredit, 0.00, $totalCredit, '', ''],
            'sections' => $sections,
        ];
    }

    private function creditColumns(): array
    {
        return ['Member ID', 'Name', 'Total Credit Taken', 'Total Paid', 'Outstanding Balance', 'Last Credit Date', 'Status'];
    }

    private function cashierSalesDefinition(array $filters, int $cashierId): array
    {
        [$start, $end] = $this->scopeRange($filters);

        $sales = Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->where('cashier_id', $cashierId)
            ->whereBetween('created_at', [$start, $end])
            ->get(['created_at', 'member_id', 'customer_type', 'payment_method', 'total']);

        $total = round((float) $sales->sum('total'), 2);
        $credit = round((float) $sales->where('payment_method', 'credit')->sum('total'), 2);
        $cash = round(max(0, $total - $credit), 2);
        $memberTotal = round((float) $sales->filter(fn (Sale $sale) => $sale->member_id !== null)->sum('total'), 2);
        $nonMemberTotal = round((float) $sales->filter(fn (Sale $sale) => $sale->member_id === null)->sum('total'), 2);

        $grouped = $sales->groupBy(function (Sale $sale) {
            return $sale->created_at?->copy()->setTimezone('Asia/Manila')->toDateString() ?: 'Unknown';
        })->sortKeys();

        $columns = ['Date', 'Revenue'];
        $writeRows = function (callable $emit) use ($grouped) {
            if ($grouped->isEmpty()) {
                $emit($this->emptyRow(2));

                return;
            }

            foreach ($grouped as $date => $daySales) {
                $emit($this->row([(string) $date, round((float) $daySales->sum('total'), 2)]));
            }
        };

        return [
            'meta' => $this->meta('my-sales', 'My Sales Report', $filters, [], $cashierId),
            'summary' => [
                ['label' => 'Total revenue', 'value' => $this->peso($total)],
                ['label' => 'Cash sales', 'value' => $this->peso($cash)],
                ['label' => 'Credit sales', 'value' => $this->peso($credit)],
                ['label' => 'Member sales', 'value' => $this->peso($memberTotal)],
                ['label' => 'Non-member sales', 'value' => $this->peso($nonMemberTotal)],
            ],
            'columns' => $columns,
            'currencyColumns' => [1],
            'writeRows' => $writeRows,
            'totals' => ['TOTAL', $total],
            'sections' => [],
        ];
    }

    private function salesDefinition(array $filters): array
    {
        if ($filters['scope'] === 'period') {
            $analytics = $this->analyticsSummary($filters);
            $total = (float) ($analytics['total_revenue'] ?? 0);
            $credit = (float) ($analytics['credit_sales'] ?? 0);
            $cash = round(max(0, $total - $credit), 2);

            $columns = ['Period', 'Revenue'];
            $rows = $analytics['data'] ?? [];

            $writeRows = function (callable $emit) use ($rows) {
                if ($rows === []) {
                    $emit($this->emptyRow(2));

                    return;
                }
                foreach ($rows as $row) {
                    $emit($this->row([(string) ($row['label'] ?? ''), (float) ($row['value'] ?? 0)]));
                }
            };

            $memberTotal = (float) ($analytics['member_total'] ?? 0);
            $nonMemberTotal = (float) ($analytics['non_member_total'] ?? 0);
            $sections = [[
                'title' => 'Revenue breakdown',
                'columns' => ['Category', 'Amount', 'Transactions'],
                'currencyColumns' => [1],
                'writeRows' => function (callable $emit) use ($analytics, $cash, $credit) {
                    $emit($this->row(['Member sales', (float) ($analytics['member_total'] ?? 0), (int) ($analytics['member_count'] ?? 0)]));
                    $emit($this->row(['Non-member sales', (float) ($analytics['non_member_total'] ?? 0), (int) ($analytics['non_member_count'] ?? 0)]));
                    $emit($this->row(['Cash sales', $cash, '']));
                    $emit($this->row(['Credit sales', $credit, (int) ($analytics['credit_count'] ?? 0)]));
                },
            ]];
        } else {
            [$start, $end] = $this->scopeRange($filters);
            $from = $start->toDateString();
            $to = $end->toDateString();

            $rows = DB::table('purchases')
                ->selectRaw('DATE(purchase_date) as day, SUM(amount) as total')
                ->whereBetween('purchase_date', [$from, $to])
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            $total = round((float) $rows->sum('total'), 2);
            $credit = round((float) Sale::query()
                ->where('payment_method', 'credit')
                ->where('status', Sale::STATUS_COMPLETED)
                ->whereBetween('created_at', [$start, $end])
                ->sum('total'), 2);
            $cash = round(max(0, $total - $credit), 2);

            $breakdown = DB::table('purchases')
                ->select('customer_type', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->whereBetween('purchase_date', [$from, $to])
                ->groupBy('customer_type')
                ->get()
                ->keyBy('customer_type');

            $memberTotal = (float) ($breakdown->get('member')->total ?? 0);
            $nonMemberTotal = (float) ($breakdown->get('non_member')->total ?? 0);

            $columns = ['Date', 'Revenue'];
            $writeRows = function (callable $emit) use ($rows) {
                if ($rows->isEmpty()) {
                    $emit($this->emptyRow(2));

                    return;
                }
                foreach ($rows as $row) {
                    $emit($this->row([$row->day, (float) $row->total]));
                }
            };

            $sections = [[
                'title' => 'Revenue breakdown',
                'columns' => ['Category', 'Amount', 'Transactions'],
                'currencyColumns' => [1],
                'writeRows' => function (callable $emit) use ($breakdown, $cash, $credit) {
                    $member = $breakdown->get('member');
                    $nonMember = $breakdown->get('non_member');
                    $emit($this->row(['Member sales', (float) ($member->total ?? 0), (int) ($member->count ?? 0)]));
                    $emit($this->row(['Non-member sales', (float) ($nonMember->total ?? 0), (int) ($nonMember->count ?? 0)]));
                    $emit($this->row(['Cash sales', $cash, '']));
                    $emit($this->row(['Credit sales', $credit, '']));
                },
            ]];
        }

        return [
            'meta' => $this->meta('sales', 'Sales Report', $filters, $analytics ?? []),
            'summary' => [
                ['label' => 'Total revenue', 'value' => $this->peso($total)],
                ['label' => 'Cash sales', 'value' => $this->peso($cash)],
                ['label' => 'Credit sales', 'value' => $this->peso($credit)],
                ['label' => 'Member sales', 'value' => $this->peso($memberTotal)],
                ['label' => 'Non-member sales', 'value' => $this->peso($nonMemberTotal)],
            ],
            'columns' => $columns,
            'currencyColumns' => [1],
            'writeRows' => $writeRows,
            'totals' => ['TOTAL', $total],
            'sections' => $sections,
        ];
    }

    private function inventoryDefinition(array $filters, ?int $cashierId = null): array
    {
        $categoryId = $filters['category_id'] ?? null;

        $totals = DB::table('items')
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->selectRaw('COUNT(*) as item_count, COALESCE(SUM(quantity), 0) as quantity, COALESCE(SUM(quantity * price), 0) as value, SUM(CASE WHEN quantity <= 10 THEN 1 ELSE 0 END) as low_count')
            ->first();

        $writeRows = function (callable $emit) use ($categoryId) {
            $found = false;
            Item::query()
                ->with('categoryInfo')
                ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
                ->orderBy('item_name')
                ->chunk(500, function ($items) use ($emit, &$found) {
                    foreach ($items as $item) {
                        $found = true;
                        $quantity = (int) $item->quantity;
                        $low = $quantity <= 10;
                        $emit($this->row([
                            $item->item_name,
                            $item->categoryInfo?->display_name ?: ($item->category ?: 'Uncategorized'),
                            $quantity,
                            (float) $item->price,
                            round($quantity * (float) $item->price, 2),
                            $low ? 'LOW STOCK' : 'OK',
                        ], $low ? 'low' : ''));
                    }
                });

            if (! $found) {
                $emit($this->emptyRow(6));
            }
        };

        return [
            'meta' => $this->meta('inventory', 'Inventory Report', $filters, [], $cashierId),
            'summary' => [
                ['label' => 'Items', 'value' => (string) (int) ($totals->item_count ?? 0)],
                ['label' => 'Units on hand', 'value' => number_format((float) ($totals->quantity ?? 0))],
                ['label' => 'Stock value', 'value' => $this->peso((float) ($totals->value ?? 0))],
                ['label' => 'Low-stock items (≤10)', 'value' => (string) (int) ($totals->low_count ?? 0)],
            ],
            'columns' => ['Item', 'Category', 'Quantity', 'Unit Price', 'Stock Value', 'Status'],
            'currencyColumns' => [3, 4],
            'writeRows' => $writeRows,
            'totals' => ['TOTAL', '', (int) ($totals->quantity ?? 0), '', round((float) ($totals->value ?? 0), 2), ''],
            'sections' => [],
        ];
    }

    private function memberPurchasesDefinition(array $filters, ?int $cashierId = null): array
    {
        [$start, $end] = $this->scopeRange($filters);
        $memberId = $filters['member_id'] ?? null;
        $from = $start->toDateString();
        $to = $end->toDateString();

        if ($cashierId !== null) {
            $base = fn () => DB::table('sales')
                ->where('status', Sale::STATUS_COMPLETED)
                ->where('cashier_id', $cashierId)
                ->whereNotNull('member_id')
                ->when($memberId, fn ($query) => $query->where('member_id', $memberId))
                ->whereBetween('created_at', [$start, $end]);
        } else {
            $base = fn () => DB::table('purchases')
                ->whereNotNull('member_id')
                ->when($memberId, fn ($query) => $query->where('member_id', $memberId))
                ->whereBetween('purchase_date', [$from, $to]);
        }

        $amountColumn = $cashierId !== null ? 'total' : 'amount';
        $dateColumn = $cashierId !== null ? 'created_at' : 'purchase_date';

        $aggregate = (clone $base())
            ->selectRaw('COUNT(*) as purchases, COALESCE(SUM('.$amountColumn.'), 0) as total, COUNT(DISTINCT member_id) as members')
            ->first();

        $query = (clone $base())
            ->selectRaw('member_id, COUNT(*) as purchase_count, COALESCE(SUM('.$amountColumn.'), 0) as total_purchases, MAX('.$dateColumn.') as last_purchase_date')
            ->groupBy('member_id')
            ->orderBy('member_id');

        $writeRows = function (callable $emit) use ($query) {
            $found = false;
            $query->chunk(500, function ($rows) use ($emit, &$found) {
                $members = Member::whereIn('id', $rows->pluck('member_id'))->get()->keyBy('id');
                foreach ($rows as $row) {
                    $found = true;
                    $member = $members->get($row->member_id);
                    $emit($this->row([
                        $member?->member_number ?: '#'.$row->member_id,
                        trim(($member?->first_name ?? '').' '.($member?->last_name ?? '')) ?: 'Unknown member',
                        (int) $row->purchase_count,
                        round((float) $row->total_purchases, 2),
                        $row->last_purchase_date ? Carbon::parse((string) $row->last_purchase_date, 'Asia/Manila')->format('M d, Y') : '—',
                    ]));
                }
            });

            if (! $found) {
                $emit($this->emptyRow(5));
            }
        };

        return [
            'meta' => $this->meta($cashierId === null ? 'member-purchases' : 'my-member-purchases', $cashierId === null ? 'Member Purchases Report' : 'My Member Purchases Report', $filters, [], $cashierId),
            'summary' => [
                ['label' => 'Members with purchases', 'value' => (string) (int) ($aggregate->members ?? 0)],
                ['label' => 'Purchases', 'value' => number_format((int) ($aggregate->purchases ?? 0))],
                ['label' => 'Total purchases', 'value' => $this->peso((float) ($aggregate->total ?? 0))],
            ],
            'columns' => ['Member ID', 'Member', 'Purchase Count', 'Total Purchases', 'Last Purchase Date'],
            'currencyColumns' => [3],
            'writeRows' => $writeRows,
            'totals' => ['TOTAL', '', (int) ($aggregate->purchases ?? 0), round((float) ($aggregate->total ?? 0), 2), ''],
            'sections' => [],
        ];
    }

    private function transactionsDefinition(array $filters, ?int $cashierId = null): array
    {
        [$start, $end] = $this->scopeRange($filters);
        $memberId = $filters['member_id'] ?? null;
        $payment = $filters['payment_method'] ?? 'all';

        $base = fn () => Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereBetween('created_at', [$start, $end])
            ->when($memberId, fn ($query) => $query->where('member_id', $memberId))
            ->when($payment !== 'all', fn ($query) => $query->where('payment_method', $payment))
            ->when($cashierId, fn ($query) => $query->where('cashier_id', $cashierId));

        $aggregate = (clone $base())
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total, COALESCE(SUM(CASE WHEN payment_method = ? THEN total ELSE 0 END), 0) as cash_total, COALESCE(SUM(CASE WHEN payment_method = ? THEN total ELSE 0 END), 0) as credit_total', ['cash', 'credit'])
            ->first();

        $writeRows = function (callable $emit) use ($base) {
            $found = false;
            (clone $base())->with(['cashier', 'member'])->orderBy('id')->chunk(500, function ($sales) use ($emit, &$found) {
                foreach ($sales as $sale) {
                    $found = true;
                    $emit($this->row([
                        $sale->created_at?->copy()->setTimezone('Asia/Manila')->format('M d, Y g:i A') ?: '—',
                        '#'.$sale->id,
                        $sale->member_name,
                        $sale->payment_method === 'credit' ? 'Credit' : 'Cash',
                        (float) $sale->total,
                        $sale->cashier_name,
                    ]));
                }
            });

            if (! $found) {
                $emit($this->emptyRow(6));
            }
        };

        return [
            'meta' => $this->meta($cashierId === null ? 'transactions' : 'my-transactions', $cashierId === null ? 'Transactions Report' : 'My Transactions Report', $filters, [], $cashierId),
            'summary' => [
                ['label' => 'Transactions', 'value' => number_format((int) ($aggregate->count ?? 0))],
                ['label' => 'Total amount', 'value' => $this->peso((float) ($aggregate->total ?? 0))],
                ['label' => 'Cash', 'value' => $this->peso((float) ($aggregate->cash_total ?? 0))],
                ['label' => 'Credit', 'value' => $this->peso((float) ($aggregate->credit_total ?? 0))],
            ],
            'columns' => ['Date', 'Transaction No.', 'Member / Customer', 'Payment Type', 'Amount', 'Cashier'],
            'currencyColumns' => [4],
            'writeRows' => $writeRows,
            'totals' => ['TOTAL', '', '', '', round((float) ($aggregate->total ?? 0), 2), ''],
            'sections' => [],
        ];
    }

    private function analyticsSummary(array $filters): array
    {
        $params = array_filter([
            'period' => $filters['period'] ?? 'daily',
            'date' => $filters['date'] ?? null,
            'month' => $filters['month'] ?? null,
            'year' => $filters['year'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $controller = new AnalyticsController;
        $sales = $controller->getSalesData(new Request($params))->getData(true);
        $breakdown = $controller->getCustomerTypeBreakdown(new Request($params))->getData(true);

        return array_merge($sales, $breakdown, [
            'cash_derived' => round(max(0, (float) ($sales['total_revenue'] ?? 0) - (float) ($sales['credit_sales'] ?? 0)), 2),
        ]);
    }

    private function scopeRange(array $filters): array
    {
        if ($filters['scope'] === 'range') {
            return [
                Carbon::createFromFormat('Y-m-d', $filters['from'], 'Asia/Manila')->startOfDay(),
                Carbon::createFromFormat('Y-m-d', $filters['to'], 'Asia/Manila')->endOfDay(),
            ];
        }

        return $this->periodRange($filters);
    }

    private function periodRange(array $filters): array
    {
        $period = $filters['period'] ?? 'daily';

        if ($period === 'daily') {
            $date = $this->validDate($filters['date'] ?? null)
                ? Carbon::createFromFormat('Y-m-d', $filters['date'], 'Asia/Manila')
                : Carbon::now('Asia/Manila');

            return [$date->copy()->startOfDay(), $date->copy()->endOfDay()];
        }

        if ($period === 'weekly') {
            $month = $filters['month'] ?? null;
            if (! $month || ! preg_match('/^\d{4}-\d{2}$/', $month)) {
                $latest = DB::table('purchases')->max('purchase_date');
                $month = $latest ? Carbon::parse($latest, 'Asia/Manila')->format('Y-m') : Carbon::now('Asia/Manila')->format('Y-m');
            }
            $selected = Carbon::createFromFormat('Y-m', $month, 'Asia/Manila')->startOfMonth();

            return [$selected->copy()->startOfMonth(), $selected->copy()->endOfMonth()->endOfDay()];
        }

        if (in_array($period, ['monthly', 'quarterly'], true)) {
            $year = $filters['year'] ?? null;
            if (! $year || ! preg_match('/^\d{4}$/', (string) $year)) {
                $latest = DB::table('purchases')->max('purchase_date');
                $year = $latest ? Carbon::parse($latest, 'Asia/Manila')->year : Carbon::now('Asia/Manila')->year;
            }
            $selected = Carbon::create((int) $year, 1, 1, 0, 0, 0, 'Asia/Manila');

            return [$selected->copy()->startOfYear(), $selected->copy()->endOfYear()->endOfDay()];
        }

        $earliest = DB::table('purchases')->min('purchase_date');
        $start = $earliest
            ? Carbon::parse($earliest, 'Asia/Manila')->startOfYear()
            : Carbon::now('Asia/Manila')->startOfYear();

        return [$start, Carbon::now('Asia/Manila')->endOfDay()];
    }

    private function meta(string $type, string $title, array $filters, array $analytics = [], ?int $cashierId = null): array
    {
        $user = auth()->user();
        $logo = public_path('images/logotunay.jpg');
        $logoData = '';
        if (is_file($logo)) {
            $logoData = 'data:image/jpeg;base64,'.base64_encode((string) file_get_contents($logo));
        }

        return [
            'type' => $type,
            'title' => $title,
            'periodLabel' => $this->periodLabel($filters, $analytics),
            'filtersLabel' => $this->filtersLabel($filters, $cashierId),
            'generatedAt' => Carbon::now('Asia/Manila')->format('M d, Y g:i A'),
            'generatedBy' => trim(($user?->name ?? 'Unknown user').(($user?->username ?? '') !== '' ? ' (@'.$user->username.')' : '')),
            'logoData' => $logoData,
            'filename' => 'ccfmpc-'.$type.'-report-'.Carbon::now('Asia/Manila')->toDateString(),
        ];
    }

    private function periodLabel(array $filters, array $analytics = []): string
    {
        if ($filters['scope'] === 'range') {
            $from = Carbon::createFromFormat('Y-m-d', $filters['from'], 'Asia/Manila');
            $to = Carbon::createFromFormat('Y-m-d', $filters['to'], 'Asia/Manila');

            return 'Date range: '.$from->format('M d, Y').' – '.$to->format('M d, Y');
        }

        $period = $filters['period'] ?? 'daily';

        if ($period === 'daily') {
            $date = $analytics['date'] ?? ($this->validDate($filters['date'] ?? null) ? $filters['date'] : Carbon::now('Asia/Manila')->toDateString());

            return 'Daily — '.Carbon::parse($date, 'Asia/Manila')->format('M d, Y');
        }

        if ($period === 'weekly') {
            $month = $filters['month'] ?? Carbon::now('Asia/Manila')->format('Y-m');

            return 'Weekly — '.Carbon::createFromFormat('Y-m', $month, 'Asia/Manila')->format('F Y');
        }

        if (in_array($period, ['monthly', 'quarterly'], true)) {
            $year = $filters['year'] ?? Carbon::now('Asia/Manila')->year;

            return ucfirst($period).' — '.$year;
        }

        return 'Yearly — all recorded years';
    }

    private function filtersLabel(array $filters, ?int $cashierId = null): string
    {
        $parts = [];

        if (! empty($filters['member_id'])) {
            $member = Member::find($filters['member_id']);
            $parts[] = 'Member: '.($member ? $member->full_name.' ('.$member->member_number.')' : '#'.$filters['member_id']);
        }

        if (! empty($filters['category_id'])) {
            $category = Category::find($filters['category_id']);
            $parts[] = 'Category: '.($category?->display_name ?? '#'.$filters['category_id']);
        }

        if (($filters['status'] ?? 'all') !== 'all') {
            $parts[] = 'Status: '.ucfirst($filters['status']);
        }

        if (($filters['payment_method'] ?? 'all') !== 'all') {
            $parts[] = 'Payment: '.ucfirst($filters['payment_method']);
        }

        if ($cashierId !== null) {
            $parts[] = 'Cashier: '.(auth()->user()?->name ?: '#'.$cashierId);
        }

        return $parts === [] ? 'None' : implode(' · ', $parts);
    }

    private function row(array $cells, string $class = ''): array
    {
        return ['cells' => $cells, 'class' => $class];
    }

    private function emptyRow(int $columns): array
    {
        $cells = array_fill(0, max(1, $columns), '');
        $cells[0] = 'No records for this period';

        return $this->row($cells);
    }

    private function peso(float $amount): string
    {
        return "\u{20B1}".number_format($amount, 2);
    }

    private function validDate(?string $value): bool
    {
        return is_string($value) && (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
    }
}
