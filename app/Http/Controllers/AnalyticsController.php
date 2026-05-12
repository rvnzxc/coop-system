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
                // Get date parameter, default to today
                $dateParam = $request->query('date');
                if (!$dateParam) {
                    // If no date specified, use the most recent date with purchases
                    $latestPurchase = Purchase::orderBy('created_at', 'desc')->first();
                    if ($latestPurchase) {
                        $selectedDate = Carbon::parse($latestPurchase->created_at)->startOfDay();
                    } else {
                        $selectedDate = Carbon::now()->startOfDay();
                    }
                } else {
                    $selectedDate = Carbon::createFromFormat('Y-m-d', $dateParam)->startOfDay();
                }
                
                \Log::info('Daily query date: ' . $selectedDate->format('Y-m-d H:i:s'));
                
                // Show hourly data for selected date using raw SQL to ensure it works
                $dateStr = $selectedDate->format('Y-m-d');
                $rows = DB::select("
                    SELECT HOUR(created_at) as grp, SUM(amount) as value 
                    FROM purchases 
                    WHERE DATE(created_at) = ? 
                    GROUP BY HOUR(created_at) 
                    ORDER BY grp
                ", [$dateStr]);
                
                // Convert to collection for consistency
                $rows = collect($rows)->keyBy('grp');
                
                \Log::info('Daily query result count: ' . $rows->count());
                if ($rows->count() > 0) {
                    \Log::info('Sample daily result: ' . json_encode($rows->first()));
                }

                for ($i = 0; $i <= 23; $i++) {
                    $hour = str_pad($i, 2, '0', STR_PAD_LEFT);
                    $key = $i;
                    $data[] = [
                        'label' => $hour . ':00',
                        'value' => isset($rows[$key]) ? (float)$rows[$key]->value : 0,
                    ];
                }
                break;

            case 'weekly':
                // Get week parameter, default to current week (show last 7 days)
                $weekParam = $request->query('week');
                if (!$weekParam) {
                    // Default to last 7 days if no week specified
                    $rows = DB::select("
                        SELECT DATE(created_at) as grp, SUM(amount) as value 
                        FROM purchases 
                        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                        GROUP BY DATE(created_at) 
                        ORDER BY grp
                    ");
                    
                    // Convert to collection
                    $rows = collect($rows)->keyBy('grp');

                    for ($i = 6; $i >= 0; $i--) {
                        $date = Carbon::now()->subDays($i);
                        $key = $date->format('Y-m-d');
                        $data[] = [
                            'label' => $date->format('D, M j'),
                            'value' => isset($rows[$key]) ? (float)$rows[$key]->value : 0,
                        ];
                    }
                } else {
                    // Use specified week
                    list($year, $week) = explode('-', $weekParam);
                    $selectedWeek = Carbon::now()->setISOWeekYear($year)->setISOWeek($week);
                    
                    $startDate = $selectedWeek->startOfWeek()->format('Y-m-d');
                    $endDate = $selectedWeek->endOfWeek()->format('Y-m-d');
                    
                    $rows = DB::select("
                        SELECT DATE(created_at) as grp, SUM(amount) as value 
                        FROM purchases 
                        WHERE created_at >= ? AND created_at <= ?
                        GROUP BY DATE(created_at) 
                        ORDER BY grp
                    ", [$startDate, $endDate]);
                    
                    // Convert to collection
                    $rows = collect($rows)->keyBy('grp');

                    for ($i = 0; $i < 7; $i++) {
                        $date = $selectedWeek->copy()->startOfWeek()->addDays($i);
                        $key = $date->format('Y-m-d');
                        $data[] = [
                            'label' => $date->format('D, M j'),
                            'value' => isset($rows[$key]) ? (float)$rows[$key]->value : 0,
                        ];
                    }
                }
                break;

            case 'monthly':
                // Get month parameter, default to current month
                $monthParam = $request->query('month');
                if (!$monthParam) {
                    // If no month specified, use the most recent month with purchases
                    $latestPurchase = Purchase::orderBy('created_at', 'desc')->first();
                    if ($latestPurchase) {
                        $selectedMonth = Carbon::parse($latestPurchase->created_at)->startOfMonth();
                    } else {
                        $selectedMonth = Carbon::now()->startOfMonth();
                    }
                } else {
                    $selectedMonth = Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth();
                }
                
                $startDate = $selectedMonth->format('Y-m-d');
                $endDate = $selectedMonth->copy()->endOfMonth()->format('Y-m-d');
                
                // Show all days in selected month
                $rows = DB::select("
                    SELECT DATE(created_at) as grp, SUM(amount) as value 
                    FROM purchases 
                    WHERE created_at >= ? AND created_at <= ?
                    GROUP BY DATE(created_at) 
                    ORDER BY grp
                ", [$startDate, $endDate]);
                
                // Convert to collection
                $rows = collect($rows)->keyBy('grp');

                $daysInMonth = $selectedMonth->daysInMonth;
                for ($i = 1; $i <= $daysInMonth; $i++) {
                    $date = $selectedMonth->copy()->startOfMonth()->addDays($i - 1);
                    $key = $date->format('Y-m-d');
                    $data[] = [
                        'label' => $date->format('M j'),
                        'value' => isset($rows[$key]) ? (float)$rows[$key]->value : 0,
                    ];
                }
                break;

            case 'yearly':
                // Get year parameter, default to current year
                $yearParam = $request->query('year');
                if (!$yearParam) {
                    // If no year specified, use the most recent year with purchases
                    $latestPurchase = Purchase::orderBy('created_at', 'desc')->first();
                    if ($latestPurchase) {
                        $selectedYear = Carbon::parse($latestPurchase->created_at)->startOfYear();
                    } else {
                        $selectedYear = Carbon::now()->startOfYear();
                    }
                } else {
                    $selectedYear = Carbon::createFromDate($yearParam)->startOfYear();
                }
                
                $startDate = $selectedYear->format('Y-m-d');
                $endDate = $selectedYear->copy()->endOfYear()->format('Y-m-d');
                
                // Show monthly data for selected year
                $rows = DB::select("
                    SELECT DATE_FORMAT(created_at, '%Y-%m') as grp, SUM(amount) as value 
                    FROM purchases 
                    WHERE created_at >= ? AND created_at <= ?
                    GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
                    ORDER BY grp
                ", [$startDate, $endDate]);
                
                // Convert to collection
                $rows = collect($rows)->keyBy('grp');

                for ($i = 1; $i <= 12; $i++) {
                    $month = $selectedYear->copy()->startOfYear()->addMonths($i - 1);
                    $key = $month->format('Y-m');
                    $data[] = [
                        'label' => $month->format('M'),
                        'value' => isset($rows[$key]) ? (float)$rows[$key]->value : 0,
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
