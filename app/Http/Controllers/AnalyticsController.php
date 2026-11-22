<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        // Fetch real purchase data from database
        $purchases = DB::table('purchases')
            ->select('purchases.*')
            ->orderBy('purchases.created_at', 'desc')
            ->get();

        // Generate real data for the dashboard
        $salesData = [
            'daily' => $this->getDailyData($purchases),
            'weekly' => $this->getWeeklyData($purchases),
            'monthly' => $this->getMonthlyData($purchases),
            'yearly' => $this->getYearlyData($purchases),
        ];

        $quarterYears = $purchases->pluck('created_at')
            ->map(fn ($createdAt) => Carbon::parse($createdAt, 'Asia/Manila')->year)
            ->unique()
            ->push(Carbon::now('Asia/Manila')->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();

        return view('analytics.index', compact('salesData', 'quarterYears'));
    }

    public function getSalesData(Request $request)
    {
        $period = $request->query('period', 'daily');
        $data = [];
        $dailyDate = null;
        $creditRange = null;

        // Debug: Check if we have any purchases at all
        $allPurchases = Purchase::all();
        \Log::info('Total purchases in database: '.$allPurchases->count());

        if ($allPurchases->count() > 0) {
            \Log::info('Sample purchase: '.json_encode($allPurchases->first()));
        }

        switch ($period) {

            case 'daily':
                // Hourly totals for one selected day (local Manila time)
                $dailyDate = $this->resolveDailyDate($request->query('date'));
                $day = Carbon::createFromFormat('Y-m-d', $dailyDate, 'Asia/Manila');
                $start = $day->copy()->startOfDay();
                $end = $day->copy()->endOfDay();
                $creditRange = [$start, $end];

                $hourly = array_fill(0, 24, 0.0);

                $sales = DB::table('sales')
                    ->select('created_at', 'total')
                    ->where('status', Sale::STATUS_COMPLETED)
                    ->whereBetween('created_at', [
                        $start->format('Y-m-d H:i:s'),
                        $end->format('Y-m-d H:i:s'),
                    ])
                    ->get();

                foreach ($sales as $sale) {
                    $hour = Carbon::parse($sale->created_at, 'Asia/Manila')->hour;
                    $hourly[$hour] += (float) $sale->total;
                }

                for ($hour = 0; $hour < 24; $hour++) {
                    $data[] = [
                        'hour' => $hour,
                        'label' => Carbon::createFromTime($hour, 0, 0, 'Asia/Manila')->format('g A'),
                        'value' => round($hourly[$hour], 2),
                        'total' => round($hourly[$hour], 2),
                    ];
                }
                break;

            case 'weekly':
                // Date-ranged week buckets inside the selected month (Manila time)
                $selectedMonth = $this->resolveMonth($request->query('month'));
                $monthStart = $selectedMonth->copy()->startOfMonth();
                $monthEnd = $selectedMonth->copy()->endOfMonth()->endOfDay();
                $startDate = $monthStart->format('Y-m-d H:i:s');
                $endDate = $monthEnd->format('Y-m-d H:i:s');
                $creditRange = [$monthStart, $monthEnd];

                $rows = DB::table('purchases')
                    ->select(DB::raw('DATE(created_at) as grp'), DB::raw('SUM(amount) as value'))
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->groupBy(DB::raw('DATE(created_at)'))
                    ->pluck('value', 'grp');

                foreach ($this->weekBucketsForMonth($selectedMonth) as $bucket) {
                    $value = 0;
                    for ($day = $bucket['start_day']; $day <= $bucket['end_day']; $day++) {
                        $key = $selectedMonth->copy()->startOfMonth()->addDays($day - 1)->format('Y-m-d');
                        $value += (float) ($rows[$key] ?? 0);
                    }

                    $data[] = [
                        'label' => $bucket['label'],
                        'value' => round($value, 2),
                    ];
                }
                break;

            case 'monthly':
                // Months within the selected year, one point per month
                $yearParam = $request->query('year');
                if (! $yearParam) {
                    // Default to the most recent year with purchases
                    $latestPurchase = Purchase::orderBy('created_at', 'desc')->first();
                    $selectedYear = $latestPurchase
                        ? Carbon::parse($latestPurchase->created_at)->startOfYear()
                        : Carbon::now()->startOfYear();
                } else {
                    $selectedYear = Carbon::createFromDate($yearParam)->startOfYear();
                }

                $monthlyStart = $selectedYear->copy()->startOfYear();
                $monthlyEnd = $selectedYear->copy()->endOfYear()->endOfDay();
                $startDate = $monthlyStart->format('Y-m-d H:i:s');
                $endDate = $monthlyEnd->format('Y-m-d H:i:s');
                $creditRange = [$monthlyStart, $monthlyEnd];

                $rows = DB::select("
                    SELECT DATE_FORMAT(created_at, '%Y-%m') as grp, SUM(amount) as value 
                    FROM purchases 
                    WHERE created_at >= ? AND created_at <= ?
                    GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
                    ORDER BY grp
                ", [$startDate, $endDate]);
                $rows = collect($rows)->keyBy('grp');

                for ($i = 1; $i <= 12; $i++) {
                    $month = $selectedYear->copy()->startOfYear()->addMonths($i - 1);
                    $key = $month->format('Y-m');
                    $data[] = [
                        'label' => $month->format('M'),
                        'value' => isset($rows[$key]) ? (float) $rows[$key]->value : 0,
                    ];
                }
                break;

            case 'yearly':
                // Annual totals across all years with purchases
                $rows = DB::select('
                    SELECT YEAR(created_at) as grp, SUM(amount) as value 
                    FROM purchases 
                    GROUP BY YEAR(created_at) 
                    ORDER BY grp
                ');
                $rows = collect($rows)->keyBy('grp');

                $years = $rows->keys()->map(function ($yr) {
                    return (int) $yr;
                });
                if ($years->isEmpty()) {
                    // No purchases at all yet; still show the current year as a zero point
                    $years = collect([Carbon::now()->year]);
                }

                foreach ($years as $yr) {
                    $data[] = [
                        'label' => (string) $yr,
                        'value' => isset($rows[$yr]) ? (float) $rows[$yr]->value : 0,
                    ];
                }
                break;

            case 'quarterly':
                // Quarters in the selected year, grouped by QUARTER(created_at) in Manila time
                $year = $this->resolveYear($request->query('year'));
                $yearStart = Carbon::create($year, 1, 1, 0, 0, 0, 'Asia/Manila');
                $yearEnd = $yearStart->copy()->endOfYear()->endOfDay();
                $creditRange = [$yearStart, $yearEnd];
                $bindings = [$yearStart->format('Y-m-d H:i:s'), $yearEnd->format('Y-m-d H:i:s')];

                if (DB::connection()->getDriverName() === 'mysql') {
                    $rows = DB::select('
                        SELECT QUARTER(created_at) as grp, SUM(amount) as value
                        FROM purchases
                        WHERE created_at >= ? AND created_at <= ?
                        GROUP BY QUARTER(created_at)
                        ORDER BY grp
                    ', $bindings);
                } else {
                    $rows = DB::select("
                        SELECT CAST((CAST(strftime('%m', created_at) AS INTEGER) - 1) / 3 AS INTEGER) + 1 as grp, SUM(amount) as value
                        FROM purchases
                        WHERE created_at >= ? AND created_at <= ?
                        GROUP BY grp
                        ORDER BY grp
                    ", $bindings);
                }
                $rows = collect($rows)->keyBy('grp');

                foreach ($this->quarterBucketsForYear($year) as $quarter) {
                    $row = $rows->get($quarter);
                    $data[] = [
                        'label' => 'Q'.$quarter,
                        'value' => round((float) ($row->value ?? 0), 2),
                    ];
                }
                break;
        }

        $values = array_column($data, 'value');
        $total = array_sum($values);
        $peak = count($values) > 0 ? max($values) : 0;

        $creditQuery = DB::table('sales')
            ->where('payment_method', 'credit')
            ->where('status', Sale::STATUS_COMPLETED);

        if ($creditRange !== null) {
            $creditQuery->whereBetween('created_at', [
                $creditRange[0]->format('Y-m-d H:i:s'),
                $creditRange[1]->format('Y-m-d H:i:s'),
            ]);
        }

        $creditSales = (float) $creditQuery->sum('total');
        $creditCount = (int) (clone $creditQuery)->count();

        $response = [
            'data' => $data,
            'total_revenue' => round($total, 2),
            'credit_sales' => round($creditSales, 2),
            'credit_count' => $creditCount,
            'peak_sales' => round($peak, 2),
        ];

        if ($dailyDate !== null) {
            $response['date'] = $dailyDate;
        }

        if (in_array($period, ['weekly', 'quarterly'], true) && $peak > 0) {
            $peakIndex = array_search($peak, $values, true);
            if ($peakIndex !== false) {
                $response['peak_label'] = $data[$peakIndex]['label'];
            }
        }

        return response()->json($response);
    }

    private function resolveDailyDate(?string $date): string
    {
        $today = Carbon::now('Asia/Manila')->format('Y-m-d');

        if (! $date || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $today;
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Manila');
        } catch (\Throwable $e) {
            return $today;
        }

        if ($parsed->format('Y-m-d') !== $date || $parsed->greaterThan(Carbon::now('Asia/Manila')->endOfDay())) {
            return $today;
        }

        return $date;
    }

    private function resolveMonth(?string $month): Carbon
    {
        $currentMonth = Carbon::now('Asia/Manila')->startOfMonth();

        if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
            try {
                $parsed = Carbon::createFromFormat('Y-m', $month, 'Asia/Manila')->startOfMonth();
                if ($parsed->format('Y-m') === $month) {
                    return $parsed->greaterThan($currentMonth) ? $currentMonth : $parsed;
                }
            } catch (\Throwable $e) {
                return $currentMonth;
            }
        }

        $latestPurchase = Purchase::orderBy('created_at', 'desc')->first();
        if (! $latestPurchase) {
            return $currentMonth;
        }

        $latestMonth = Carbon::parse($latestPurchase->created_at, 'Asia/Manila')->startOfMonth();

        return $latestMonth->greaterThan($currentMonth) ? $currentMonth : $latestMonth;
    }

    private function resolveYear(mixed $year): int
    {
        if (is_numeric($year) && preg_match('/^\d{4}$/', (string) $year)) {
            return (int) $year;
        }

        return Carbon::now('Asia/Manila')->year;
    }

    private function weekBucketsForMonth(Carbon $month): array
    {
        $today = Carbon::now('Asia/Manila')->startOfDay();
        $buckets = [];
        $day = 1;

        while ($day <= $month->daysInMonth) {
            $endDay = min($day + 6, $month->daysInMonth);
            $start = $month->copy()->startOfMonth()->addDays($day - 1)->startOfDay();

            if ($start->greaterThan($today)) {
                break;
            }

            $end = $month->copy()->startOfMonth()->addDays($endDay - 1)->endOfDay();
            $buckets[] = [
                'start_day' => $day,
                'end_day' => $endDay,
                'start' => $start,
                'end' => $end,
                'label' => $start->format('M j').'-'.$end->format('j'),
            ];
            $day = $endDay + 1;
        }

        return $buckets;
    }

    private function quarterBucketsForYear(int $year): array
    {
        $today = Carbon::now('Asia/Manila')->startOfDay();

        return collect(range(1, 4))
            ->filter(function (int $quarter) use ($year, $today) {
                $start = Carbon::create($year, (($quarter - 1) * 3) + 1, 1, 0, 0, 0, 'Asia/Manila');

                return ! $start->greaterThan($today);
            })
            ->values()
            ->all();
    }

    private function getDailyData($purchases)
    {
        $dailyData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayPurchases = $purchases->filter(function ($purchase) use ($date) {
                return Carbon::parse($purchase->created_at)->format('Y-m-d') === $date;
            });

            $dailyData[] = [
                'date' => Carbon::parse($date)->format('M d'),
                'sales' => $dayPurchases->sum('amount'),
            ];
        }

        return $dailyData;
    }

    private function getWeeklyData($purchases)
    {
        $month = Carbon::now('Asia/Manila')->startOfMonth();

        return collect($this->weekBucketsForMonth($month))->map(function (array $bucket) use ($purchases) {
            $sales = $purchases->filter(function ($purchase) use ($bucket) {
                $purchaseDate = Carbon::parse($purchase->created_at, 'Asia/Manila');

                return $purchaseDate->between($bucket['start'], $bucket['end']);
            })->sum('amount');

            return [
                'date' => $bucket['label'],
                'sales' => $sales,
            ];
        })->all();
    }

    private function getMonthlyData($purchases)
    {
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $monthPurchases = $purchases->filter(function ($purchase) use ($monthStart, $monthEnd) {
                $purchaseDate = Carbon::parse($purchase->created_at);

                return $purchaseDate->between($monthStart, $monthEnd);
            });

            $monthlyData[] = [
                'date' => $month->format('M'),
                'sales' => $monthPurchases->sum('amount'),
            ];
        }

        return $monthlyData;
    }

    private function getYearlyData($purchases)
    {
        $yearlyData = [];
        for ($i = 4; $i >= 0; $i--) {
            $year = Carbon::now()->subYears($i);
            $yearStart = $year->copy()->startOfYear();
            $yearEnd = $year->copy()->endOfYear();

            $yearPurchases = $purchases->filter(function ($purchase) use ($yearStart, $yearEnd) {
                $purchaseDate = Carbon::parse($purchase->created_at);

                return $purchaseDate->between($yearStart, $yearEnd);
            });

            $yearlyData[] = [
                'date' => $year->format('Y'),
                'sales' => $yearPurchases->sum('amount'),
            ];
        }

        return $yearlyData;
    }

    public function getCustomerTypeBreakdown(Request $request)
    {
        $period = $request->query('period', 'monthly');

        if ($period === 'daily') {
            $date = $this->resolveDailyDate($request->query('date'));
            $day = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Manila');

            $breakdown = DB::table('sales')
                ->select('customer_type', DB::raw('SUM(total) as total'), DB::raw('COUNT(*) as count'))
                ->where('status', Sale::STATUS_COMPLETED)
                ->whereBetween('created_at', [
                    $day->copy()->startOfDay()->format('Y-m-d H:i:s'),
                    $day->copy()->endOfDay()->format('Y-m-d H:i:s'),
                ])
                ->groupBy('customer_type')
                ->get();

            return response()->json($this->formatCustomerTypeBreakdown($breakdown));
        }

        $startDate = null;
        $endDate = null;
        $dateColumn = 'purchase_date';

        switch ($period) {
            case 'weekly':
                $monthParam = $request->query('month');
                if ($monthParam) {
                    $selectedMonth = Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth();
                } else {
                    $selectedMonth = Carbon::now()->startOfMonth();
                }
                $startDate = $selectedMonth->format('Y-m-d');
                $endDate = $selectedMonth->copy()->endOfMonth()->format('Y-m-d');
                break;

            case 'monthly':
                $yearParam = $request->query('year');
                if ($yearParam) {
                    $selectedYear = Carbon::createFromDate($yearParam)->startOfYear();
                } else {
                    $selectedYear = Carbon::now()->startOfYear();
                }
                $startDate = $selectedYear->format('Y-m-d');
                $endDate = $selectedYear->copy()->endOfYear()->format('Y-m-d');
                break;

            case 'yearly':
                $startDate = null;
                $endDate = null;
                break;

            case 'quarterly':
                $year = $this->resolveYear($request->query('year'));
                $yearStart = Carbon::create($year, 1, 1, 0, 0, 0, 'Asia/Manila');
                $startDate = $yearStart->format('Y-m-d H:i:s');
                $endDate = $yearStart->copy()->endOfYear()->endOfDay()->format('Y-m-d H:i:s');
                $dateColumn = 'created_at';
                break;
        }

        $query = DB::table('purchases')
            ->select('customer_type', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'));

        if ($startDate && $endDate) {
            $query->whereBetween($dateColumn, [$startDate, $endDate]);
        }

        $breakdown = $query->groupBy('customer_type')->get();

        return response()->json($this->formatCustomerTypeBreakdown($breakdown));
    }

    private function formatCustomerTypeBreakdown($breakdown): array
    {
        $memberTotal = 0;
        $nonMemberTotal = 0;
        $memberCount = 0;
        $nonMemberCount = 0;

        foreach ($breakdown as $row) {
            if ($row->customer_type === 'member') {
                $memberTotal = (float) $row->total;
                $memberCount = (int) $row->count;
            } else {
                $nonMemberTotal = (float) $row->total;
                $nonMemberCount = (int) $row->count;
            }
        }

        return [
            'member_total' => round($memberTotal, 2),
            'non_member_total' => round($nonMemberTotal, 2),
            'member_count' => $memberCount,
            'non_member_count' => $nonMemberCount,
        ];
    }
}
