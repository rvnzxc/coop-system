<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MyTransactionController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(Auth::check(), 403);

        $validated = $request->validate([
            'payment_method' => ['nullable', 'string', 'in:cash,credit'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        if (! $dateFrom && ! $dateTo) {
            $dateFrom = now()->toDateString();
            $dateTo = now()->toDateString();
        }

        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            throw ValidationException::withMessages([
                'date_to' => 'The end date must be on or after the start date.',
            ]);
        }

        $paymentMethod = $validated['payment_method'] ?? null;

        // The cashier is always taken from the session. Other cashiers' sales
        // are impossible to reach because no cashier filter is accepted.
        $query = Sale::query()
            ->where('cashier_id', Auth::id())
            ->when($paymentMethod, function (Builder $query) use ($paymentMethod) {
                $query->where('payment_method', $paymentMethod);
            })
            ->when($dateFrom, function (Builder $query) use ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($dateTo, function (Builder $query) use ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            });

        $summaryBase = clone $query;
        $completed = (clone $summaryBase)->where('status', Sale::STATUS_COMPLETED);

        $summary = [
            'transactions' => (clone $summaryBase)->count(),
            'total_sales' => (float) (clone $completed)->sum('total'),
        ];

        $sales = $query
            ->with(['cashier', 'member', 'items'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('my-transactions.index', [
            'sales' => $sales,
            'summary' => $summary,
            'paymentMethod' => $paymentMethod,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }
}
