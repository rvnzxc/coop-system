<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreditController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'analytics' ? 'analytics' : 'credits';
        $from = $this->parseDate($request->query('from'));
        $to = $this->parseDate($request->query('to'));

        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        $credits = Credit::with('member')->orderBy('created_at', 'desc')->get();

        $creditRankings = [
            'all' => $this->rankCreditMembers(Credit::with('member')->get()),
        ];

        $groupedCredits = $credits->groupBy('member_id')->map(function ($group) {
            $member = $group->first()->member;

            return [
                'member'  => $member,
                'credits' => $group->values(),
                'total'   => round($group->sum('amount'), 2),
            ];
        });

        $totalCredit = round((float) Credit::sum('amount'), 2);
        $creditCount = Credit::count();
        $membersWithCredit = Credit::distinct('member_id')->count('member_id');

        $creditAnalytics = $this->creditAnalytics($from, $to);

        return view('credits.index', compact(
            'groupedCredits',
            'totalCredit',
            'creditCount',
            'membersWithCredit',
            'tab',
            'from',
            'to',
            'creditAnalytics',
            'creditRankings'
        ));
    }

    private function rankCreditMembers($credits): array
    {
        return $credits
            ->groupBy('member_id')
            ->map(function ($group) {
                $member = $group->first()->member;

                return [
                    'member_id' => $member->member_number,
                    'name'      => trim($member->first_name . ' ' . $member->last_name),
                    'total'     => round($group->sum('amount'), 2),
                ];
            })
            ->sortByDesc('total')
            ->take(5)
            ->values()
            ->all();
    }

    private function parseDate($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function creditAnalytics(?string $from, ?string $to): array
    {
        $salesQuery = Sale::query()
            ->where('payment_method', 'credit')
            ->where('status', Sale::STATUS_COMPLETED)
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to));

        $totalSales = round((float) (clone $salesQuery)->sum('total'), 2);
        $transactionCount = (clone $salesQuery)->count();

        $rows = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('items', 'sale_items.item_id', '=', 'items.id')
            ->where('sales.payment_method', 'credit')
            ->where('sales.status', Sale::STATUS_COMPLETED)
            ->when($from, fn ($query) => $query->whereDate('sales.created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('sales.created_at', '<=', $to))
            ->selectRaw("COALESCE(NULLIF(items.category, ''), 'Uncategorized') as category")
            ->addSelect('sale_items.product_name as name')
            ->selectRaw('SUM(sale_items.quantity) as quantity')
            ->groupBy('category', 'sale_items.product_name')
            ->get();

        $totalQuantity = (int) $rows->sum('quantity');

        $categories = $rows
            ->groupBy('category')
            ->map(function ($group, $category) use ($totalQuantity) {
                $items = $group
                    ->map(fn ($row) => [
                        'name' => $row->name,
                        'quantity' => (int) $row->quantity,
                    ])
                    ->sortByDesc('quantity')
                    ->values();

                $categoryQuantity = (int) $items->sum('quantity');

                return [
                    'name' => $category,
                    'quantity' => $categoryQuantity,
                    'percentage' => $totalQuantity > 0
                        ? round($categoryQuantity / $totalQuantity * 100, 1)
                        : 0.0,
                    'items' => $items->map(fn ($item) => [
                        'name' => $item['name'],
                        'quantity' => $item['quantity'],
                        'percentage' => $categoryQuantity > 0
                            ? round($item['quantity'] / $categoryQuantity * 100, 1)
                            : 0.0,
                    ])->all(),
                ];
            })
            ->sortByDesc('quantity')
            ->values();

        return [
            'total_sales' => $totalSales,
            'transactions' => $transactionCount,
            'total_quantity' => $totalQuantity,
            'top_category' => $categories->first(),
            'categories' => $categories->all(),
        ];
    }

    public function show(Credit $credit)
    {
        $credit->load('member');

        return view('credits.show', compact('credit'));
    }
}
