<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Purchase;

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
            'yearly' => $this->getYearlyData($purchases)
        ];

        return view('analytics.index', compact('salesData'));
    }

    public function getSalesData(Request $request)
    {
        $period = $request->query('period', 'daily');
        $data = [];

        // Debug: Check if we have any purchases at all
        $allPurchases = Purchase::all();
        \Log::info('Total purchases in database: ' . $allPurchases->count());
        
        if ($allPurchases->count() > 0) {
            \Log::info('Sample purchase: ' . json_encode($allPurchases->first()));
        }

        switch ($period) {

            case 'daily':
                $rows = Purchase::selectRaw("DATE(created_at) as grp, SUM(amount) as value")
                    ->where('created_at', '>=', Carbon::now()->subDays(29)->startOfDay())
                    ->groupBy('grp')->orderBy('grp')->get()->keyBy('grp');
                
                \Log::info('Daily query result count: ' . $rows->count());

                for ($i = 29; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $key = $date->format('Y-m-d');
                    $data[] = [
                        'label' => $date->format('M j'),
                        'value' => isset($rows[$key]) ? (float)$rows[$key]->value : 0,
                    ];
                }
                break;

            case 'weekly':
                $rows = Purchase::selectRaw("YEARWEEK(created_at, 1) as grp, SUM(amount) as value")
                    ->where('created_at', '>=', Carbon::now()->subWeeks(11)->startOfWeek())
                    ->groupBy('grp')->orderBy('grp')->get()->keyBy('grp');

                for ($i = 11; $i >= 0; $i--) {
                    $week = Carbon::now()->subWeeks($i)->startOfWeek();
                    $key = $week->format('oW'); // YEARWEEK format
                    $data[] = [
                        'label' => 'Week ' . (12 - $i),
                        'value' => isset($rows[$key]) ? (float)$rows[$key]->value : 0,
                    ];
                }
                break;

            case 'monthly':
                $rows = Purchase::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as grp, SUM(amount) as value")
                    ->where('created_at', '>=', Carbon::now()->subMonths(11)->startOfMonth())
                    ->groupBy('grp')->orderBy('grp')->get()->keyBy('grp');

                for ($i = 11; $i >= 0; $i--) {
                    $month = Carbon::now()->subMonths($i);
                    $key = $month->format('Y-m');
                    $data[] = [
                        'label' => $month->format('M'),
                        'value' => isset($rows[$key]) ? (float)$rows[$key]->value : 0,
                    ];
                }
                break;

            case 'yearly':
                $rows = Purchase::selectRaw("YEAR(created_at) as grp, SUM(amount) as value")
                    ->where('created_at', '>=', Carbon::now()->subYears(4)->startOfYear())
                    ->groupBy('grp')->orderBy('grp')->get()->keyBy('grp');

                for ($i = 4; $i >= 0; $i--) {
                    $year = Carbon::now()->subYears($i)->year;
                    $data[] = [
                        'label' => (string)$year,
                        'value' => isset($rows[$year]) ? (float)$rows[$year]->value : 0,
                    ];
                }
                break;
        }

        $values = array_column($data, 'value');
        $total = array_sum($values);
        $avg = count($values) > 0 ? $total / count($values) : 0;
        $peak = count($values) > 0 ? max($values) : 0;

        return response()->json([
            'data'          => $data,
            'total_revenue' => round($total, 2),
            'average_sales' => round($avg, 2),
            'peak_sales'    => round($peak, 2),
        ]);
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
                'sales' => $dayPurchases->sum('amount')
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
                'date' => 'Week ' . ($weekStart->weekOfYear),
                'sales' => $weekPurchases->sum('amount')
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
                'sales' => $monthPurchases->sum('amount')
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
                'sales' => $yearPurchases->sum('amount')
            ];
        }
        return $yearlyData;
    }
}
