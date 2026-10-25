<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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

        $filename = 'transactions-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($sales) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Transaction ID',
                'Date & Time',
                'Cashier',
                'Member',
                'Payment Method',
                'Status',
                'Total',
                'Items',
            ]);

            foreach ($sales as $sale) {
                $items = $sale->items->map(function ($item) {
                    return $item->product_name.' x'.$item->quantity.' @ '.number_format((float) $item->unit_price, 2, '.', '');
                })->implode('; ');

                fputcsv($handle, [
                    $this->csvValue((string) $sale->id),
                    $this->csvValue($sale->created_at?->format('Y-m-d H:i:s')),
                    $this->csvValue($sale->cashier_name),
                    $this->csvValue($sale->member_name),
                    $this->csvValue($sale->payment_method),
                    $this->csvValue($sale->status_label),
                    number_format((float) $sale->total, 2, '.', ''),
                    $this->csvValue($items),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
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
        return Member::query()->orderBy('last_name')->orderBy('first_name')->get();
    }

    private function csvValue(?string $value): string
    {
        $value = (string) $value;

        // Prevent spreadsheet formula injection for user-controlled values.
        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }

    private function ensureAdmin(): void
    {
        abort_unless(Auth::check() && Auth::user()->role === 'admin', 403, 'Access denied. Admin access required.');
    }
}
