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

        return view('analytics.index', compact('salesData'));
    }

    public function getSalesData(Request $request)
    {
        $period = $request->query('period', 'daily');
        $data = [];
        $dailyDate = null;

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
                // ISO weeks within the selected month, summed per week
                $monthParam = $request->query('month');
                if (! $monthParam) {
                    // Default to the most recent month with purchases
                    $latestPurchase = Purchase::orderBy('created_at', 'desc')->first();
                    $selectedMonth = $latestPurchase
                        ? Carbon::parse($latestPurchase->created_at)->startOfMonth()
                        : Carbon::now()->startOfMonth();
                } else {
                    $selectedMonth = Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth();
                }

                $startDate = $selectedMonth->format('Y-m-d');
                $endDate = $selectedMonth->copy()->endOfMonth()->format('Y-m-d');

                // Daily totals for the selected month, then roll up into ISO weeks
                $rows = DB::select('
                    SELECT DATE(created_at) as grp, SUM(amount) as value 
                    FROM purchases 
                    WHERE created_at >= ? AND created_at <= ?
                    GROUP BY DATE(created_at) 
                    ORDER BY grp
                ', [$startDate, $endDate]);
                $rows = collect($rows)->keyBy('grp');

                $weeks = [];
                for ($i = 1; $i <= $selectedMonth->daysInMonth; $i++) {
                    $date = $selectedMonth->copy()->startOfMonth()->addDays($i - 1);
                    $key = $date->format('Y-m-d');
                    $weekKey = $date->isoWeekYear().'-'.str_pad((string) $date->isoWeek(), 2, '0', STR_PAD_LEFT);
                    if (! isset($weeks[$weekKey])) {
                        $weeks[$weekKey] = [
                            'label' => 'W'.$date->isoWeek(),
                            'value' => 0,
                        ];
                    }
                    $weeks[$weekKey]['value'] += isset($rows[$key]) ? (float) $rows[$key]->value : 0;
                }
                $data = array_values($weeks);
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

                $startDate = $selectedYear->format('Y-m-d');
                $endDate = $selectedYear->copy()->endOfYear()->format('Y-m-d');

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
        }

        $values = array_column($data, 'value');
        $total = array_sum($values);
        $avg = count($values) > 0 ? $total / count($values) : 0;
        $peak = count($values) > 0 ? max($values) : 0;

        $response = [
            'data' => $data,
            'total_revenue' => round($total, 2),
            'average_sales' => round($avg, 2),
            'peak_sales' => round($peak, 2),
        ];

        if ($dailyDate !== null) {
            $response['date'] = $dailyDate;
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
        $weeklyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();

            $weekPurchases = $purchases->filter(function ($purchase) use ($weekStart, $weekEnd) {
                $purchaseDate = Carbon::parse($purchase->created_at);

                return $purchaseDate->between($weekStart, $weekEnd);
            });

            $weeklyData[] = [
                'date' => 'Week '.($weekStart->weekOfYear),
                'sales' => $weekPurchases->sum('amount'),
            ];
        }

        return $weeklyData;
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
        }

        $query = DB::table('purchases')
            ->select('customer_type', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'));

        if ($startDate && $endDate) {
            $query->whereBetween('purchase_date', [$startDate, $endDate]);
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
