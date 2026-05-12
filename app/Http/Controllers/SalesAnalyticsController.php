<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->input('period', 'monthly'); // weekly, monthly, yearly
        $memberId = $request->input('member_id');
        
        $query = DB::table('purchases')
            ->join('members', 'purchases.member_id', '=', 'members.id')
            ->join('products', 'purchases.product_id', '=', 'products.id')
            ->select('purchases.*', 'members.first_name', 'members.last_name', 'members.member_number', 'products.name as product_name', 'products.price')
            ->orderBy('purchases.created_at', 'desc');
        
        if ($memberId) {
            $query->where('purchases.member_id', $memberId);
        }
        
        $purchases = $query->get();
        
        // Calculate analytics based on period
        $analytics = $this->calculateAnalytics($purchases, $period);
        
        // Get all members for member filter dropdown
        $members = Member::orderBy('last_name', 'asc')->get();
        
        return view('sales-analytics.index', compact('analytics', 'period', 'members', 'memberId', 'purchases'));
    }
    
    public function printReport(Request $request)
    {
        $period = $request->input('period', 'monthly');
        $memberId = $request->input('member_id');
        
        $query = DB::table('purchases')
            ->join('members', 'purchases.member_id', '=', 'members.id')
            ->join('products', 'purchases.product_id', '=', 'products.id')
            ->select('purchases.*', 'members.first_name', 'members.last_name', 'members.member_number', 'products.name as product_name', 'products.price')
            ->orderBy('purchases.created_at', 'desc');
        
        if ($memberId) {
            $query->where('purchases.member_id', $memberId);
        }
        
        $purchases = $query->get();
        $analytics = $this->calculateAnalytics($purchases, $period);
        
        return view('sales-analytics.print', compact('analytics', 'period', 'memberId', 'purchases'));
    }
    
    private function calculateAnalytics($purchases, $period)
    {
        $now = Carbon::now();
        $analytics = [
            'total_sales' => 0,
            'total_transactions' => count($purchases),
            'average_transaction' => 0,
            'period_data' => [],
            'member_analytics' => [],
            'top_products' => [],
        ];
        
        if (count($purchases) === 0) {
            return $analytics;
        }
        
        // Group by member for member analytics
        $memberGroups = $purchases->groupBy('member_id');
        foreach ($memberGroups as $memberId => $memberPurchases) {
            $total = $memberPurchases->sum('price');
            $count = count($memberPurchases);
            $analytics['member_analytics'][$memberId] = [
                'member_name' => $memberPurchases->first()->first_name . ' ' . $memberPurchases->first()->last_name,
                'member_number' => $memberPurchases->first()->member_number,
                'total_purchases' => $total,
                'transaction_count' => $count,
                'average_purchase' => $count > 0 ? $total / $count : 0,
                'last_purchase' => $memberPurchases->first()->created_at,
            ];
        }
        
        // Group by period
        switch ($period) {
            case 'weekly':
                $analytics['period_data'] = $this->getWeeklyData($purchases);
                break;
            case 'monthly':
                $analytics['period_data'] = $this->getMonthlyData($purchases);
                break;
            case 'yearly':
                $analytics['period_data'] = $this->getYearlyData($purchases);
                break;
        }
        
        // Calculate totals
        $analytics['total_sales'] = $purchases->sum('price');
        $analytics['average_transaction'] = count($purchases) > 0 ? $analytics['total_sales'] / count($purchases) : 0;
        
        // Get top selling products
        $productGroups = $purchases->groupBy('product_id');
        $topProducts = [];
        foreach ($productGroups as $productId => $productPurchases) {
            $topProducts[] = [
                'product_name' => $productPurchases->first()->product_name,
                'total_sold' => count($productPurchases),
                'total_revenue' => $productPurchases->sum('price'),
            ];
        }
        
        usort($topProducts, function ($a, $b) {
            return $b['total_revenue'] <=> $a['total_revenue'];
        });
        
        $analytics['top_products'] = array_slice($topProducts, 0, 10);
        
        return $analytics;
    }
    
    private function getWeeklyData($purchases)
    {
        $weeklyData = [];
        $currentWeek = Carbon::now()->startOfWeek();
        
        for ($i = 0; $i < 12; $i++) {
            $weekStart = $currentWeek->copy()->subWeeks($i);
            $weekEnd = $weekStart->copy()->endOfWeek();
            
            $weekPurchases = $purchases->filter(function ($purchase) use ($weekStart, $weekEnd) {
                $purchaseDate = Carbon::parse($purchase->created_at);
                return $purchaseDate->between($weekStart, $weekEnd);
            });
            
            $weeklyData[] = [
                'period' => $weekStart->format('M d') . ' - ' . $weekEnd->format('M d'),
                'total_sales' => $weekPurchases->sum('price'),
                'transaction_count' => count($weekPurchases),
                'week_number' => $weekStart->weekOfYear,
                'year' => $weekStart->year,
            ];
        }
        
        return array_reverse($weeklyData);
    }
    
    private function getMonthlyData($purchases)
    {
        $monthlyData = [];
        $currentMonth = Carbon::now()->startOfMonth();
        
        for ($i = 0; $i < 12; $i++) {
            $monthStart = $currentMonth->copy()->subMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();
            
            $monthPurchases = $purchases->filter(function ($purchase) use ($monthStart, $monthEnd) {
                $purchaseDate = Carbon::parse($purchase->created_at);
                return $purchaseDate->between($monthStart, $monthEnd);
            });
            
            $monthlyData[] = [
                'period' => $monthStart->format('F Y'),
                'total_sales' => $monthPurchases->sum('price'),
                'transaction_count' => count($monthPurchases),
                'month_number' => $monthStart->month,
                'year' => $monthStart->year,
            ];
        }
        
        return array_reverse($monthlyData);
    }
    
    private function getYearlyData($purchases)
    {
        $yearlyData = [];
        $currentYear = Carbon::now()->year;
        
        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear - $i;
            $yearStart = Carbon::create($year, 1, 1);
            $yearEnd = Carbon::create($year, 12, 31);
            
            $yearPurchases = $purchases->filter(function ($purchase) use ($year) {
                $purchaseDate = Carbon::parse($purchase->created_at);
                return $purchaseDate->year == $year;
            });
            
            $yearlyData[] = [
                'period' => $year,
                'total_sales' => $yearPurchases->sum('price'),
                'transaction_count' => count($yearPurchases),
            ];
        }
        
        return $yearlyData;
    }
}
