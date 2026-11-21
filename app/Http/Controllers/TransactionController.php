<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Sale;
use App\Models\User;
use App\Services\XlsxWriter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureAdmin();

        $filters = $this->validatedFilters($request);
        $sales = $this->filteredQuery($filters)
            ->with(['cashier', 'member', 'items'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $summaryBase = $this->filteredQuery($filters);
        $completed = (clone $summaryBase)->where('status', Sale::STATUS_COMPLETED);

        $summary = [
            'transactions' => (clone $summaryBase)->count(),
            'total_sales' => (float) (clone $completed)->sum('total'),
            'cash_total' => (float) (clone $completed)->where('payment_method', 'cash')->sum('total'),
            'credit_total' => (float) (clone $completed)->where('payment_method', 'credit')->sum('total'),
        ];

        $perCashier = $this->perCashierSummary($filters);

        return view('transactions.index', [
            'sales' => $sales,
            'filters' => $filters,
            'summary' => $summary,
            'perCashier' => $perCashier,
            'cashiers' => $this->cashiers(),
            'members' => $this->members(),
        ]);
    }

    public function export(Request $request)
    {
        $this->ensureAdmin();

        $filters = $this->validatedFilters($request);
        $sales = $this->filteredQuery($filters)
            ->with(['cashier', 'member', 'items'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        if ($sales->isEmpty()) {
            $message = 'No transactions to export.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()
                ->route('transactions.index', $request->query())
                ->with('error', $message);
        }

        $rows = [[
            'Date / Time',
            'Transaction ID',
            'Cashier',
            'Member',
            'Payment',
            'Total',
            'Status',
            'Items',
        ]];

        foreach ($sales as $sale) {
            $date = $sale->created_at?->copy()->setTimezone('Asia/Manila');
            $items = $sale->items
                ->map(fn ($item) => $item->product_name.' x'.$item->quantity)
                ->implode(', ');

            $rows[] = [
                $date
                    ? [
                        'value' => $this->excelDateTime($date),
                        'type' => 'date',
                        'style' => 'datetime',
                        'display' => $date->format('M d, Y g:i A'),
                    ]
                    : ['value' => '', 'style' => 'body'],
                ['value' => '#'.$sale->id, 'style' => 'body'],
                ['value' => $sale->cashier_name, 'style' => 'body'],
                ['value' => $sale->member_name, 'style' => 'body'],
                ['value' => $sale->payment_method === 'credit' ? 'Credit' : 'Cash', 'style' => 'body'],
                ['value' => (float) $sale->total, 'type' => 'number', 'style' => 'currency'],
                ['value' => $sale->status_label, 'style' => 'body'],
                ['value' => $items !== '' ? $items : '—', 'style' => 'body'],
            ];
        }

        $lastDataRow = count($sales) + 1;
        $rows[] = [
            ['value' => 'TOTAL', 'style' => 'total'],
            ['value' => '', 'style' => 'total'],
            ['value' => '', 'style' => 'total'],
            ['value' => '', 'style' => 'total'],
            ['value' => '', 'style' => 'total'],
            [
                'value' => round((float) $sales->sum('total'), 2),
                'type' => 'formula',
                'formula' => 'SUM(F2:F'.$lastDataRow.')',
                'style' => 'total_currency',
            ],
            ['value' => '', 'style' => 'total'],
            ['value' => '', 'style' => 'total'],
        ];

        $contents = XlsxWriter::fromRows($rows, 'Transactions', [
            'column_widths' => [22, 16, 20, 28, 12, 14, 14, 50],
            'freeze_header' => true,
        ]);

        return response($contents, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$this->exportFilename($filters).'"',
            'Content-Length' => (string) strlen($contents),
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function activity(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'action' => ['nullable', 'string', 'in:'.implode(',', array_keys(AuditLog::ACTIONS))],
        ]);

        $userId = $validated['user_id'] ?? null;
        $action = $validated['action'] ?? null;

        $logs = AuditLog::query()
            ->with('user')
            ->when($userId, function (Builder $query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->when($action, function (Builder $query) use ($action) {
                $query->where('action', $action);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('transactions.activity', [
            'logs' => $logs,
            'userId' => $userId,
            'action' => $action,
            'users' => User::orderBy('name')->get(),
            'actions' => AuditLog::ACTIONS,
        ]);
    }

    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'cashier_id' => ['nullable', 'integer', 'exists:users,id'],
            'member_id' => ['nullable', 'integer', 'exists:members,id'],
            'payment_method' => ['nullable', 'string', 'in:cash,credit'],
            'status' => ['nullable', 'string', 'in:completed,voided'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            throw ValidationException::withMessages([
                'date_to' => 'The end date must be on or after the start date.',
            ]);
        }

        return [
            'cashier_id' => $validated['cashier_id'] ?? null,
            'member_id' => $validated['member_id'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
            'status' => $validated['status'] ?? null,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    private function filteredQuery(array $filters): Builder
    {
        return Sale::query()
            ->when($filters['cashier_id'], function (Builder $query) use ($filters) {
                $query->where('cashier_id', $filters['cashier_id']);
            })
            ->when($filters['member_id'], function (Builder $query) use ($filters) {
                $query->where('member_id', $filters['member_id']);
            })
            ->when($filters['payment_method'], function (Builder $query) use ($filters) {
                $query->where('payment_method', $filters['payment_method']);
            })
            ->when($filters['status'], function (Builder $query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when($filters['date_from'], function (Builder $query) use ($filters) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            })
            ->when($filters['date_to'], function (Builder $query) use ($filters) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            });
    }

    private function perCashierSummary(array $filters)
    {
        $rows = Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->when($filters['date_from'], function (Builder $query) use ($filters) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            })
            ->when($filters['date_to'], function (Builder $query) use ($filters) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            })
            ->selectRaw('cashier_id, COUNT(*) as transactions, COALESCE(SUM(total), 0) as total_sales')
            ->groupBy('cashier_id')
            ->get();

        $names = User::query()
            ->whereIn('id', $rows->pluck('cashier_id')->filter()->unique())
            ->pluck('name', 'id');

        return $rows
            ->map(function ($row) use ($names) {
                return [
                    'cashier' => $row->cashier_id ? ($names[$row->cashier_id] ?? 'Unknown') : 'Unknown',
                    'transactions' => (int) $row->transactions,
                    'total_sales' => (float) $row->total_sales,
                ];
            })
            ->sortByDesc('total_sales')
            ->values();
    }

    private function cashiers()
    {
        return User::query()->where('role', 'cashier')->orderBy('name')->get();
    }

    private function members()
    {
        return Member::active()->orderBy('last_name')->orderBy('first_name')->get();
    }

    private function exportFilename(array $filters): string
    {
        $today = Carbon::now('Asia/Manila')->format('Y-m-d');
        $from = $filters['date_from'];
        $to = $filters['date_to'];

        if ($from && $to) {
            $suffix = $from.'_to_'.$to;
        } elseif ($from) {
            $suffix = $from.'_to_'.$today;
        } elseif ($to) {
            $suffix = 'up_to_'.$to;
        } else {
            $suffix = $today;
        }

        return 'CCFMPC_Transactions_'.$suffix.'.xlsx';
    }

    private function excelDateTime(\DateTimeInterface $date): float
    {
        $wallClock = Carbon::create(
            (int) $date->format('Y'),
            (int) $date->format('n'),
            (int) $date->format('j'),
            (int) $date->format('G'),
            (int) $date->format('i'),
            (int) $date->format('s'),
            'UTC',
        );

        return ($wallClock->getTimestamp() / 86400) + 25569;
    }

    private function ensureAdmin(): void
    {
        abort_unless(Auth::check() && Auth::user()->role === 'admin', 403, 'Access denied. Admin access required.');
    }
}
