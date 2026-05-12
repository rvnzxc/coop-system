@extends('layouts.app')

@section('title', 'Sales Performance Analytics')

@section('content')
<div style="max-width: 1400px; margin: 30px auto; padding: 0 20px;">
    <h2 style="color: #1b3a1b; font-size: 28px; font-weight: bold; margin-bottom: 30px;">Sales Performance Analytics</h2>
    
    @php
        // Direct database query for testing
        use App\Models\Purchase;
        use Carbon\Carbon;
        
        // Get all purchases for debugging
        $allPurchases = Purchase::all();
        $purchaseCount = $allPurchases->count();
        
        // Get daily data for last 7 days (simpler for testing)
        $dailyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $label = $date->format('M j');
            
            $dayTotal = Purchase::whereDate('created_at', $dateStr)->sum('amount');
            $dailyData[] = ['label' => $label, 'value' => (float)$dayTotal];
        }
        
        // Calculate KPIs
        $totalRevenue = $allPurchases->sum('amount');
        $averageSales = $purchaseCount > 0 ? $totalRevenue / $purchaseCount : 0;
        $peakSales = $allPurchases->max('amount') ?: 0;
        
        // Format for display
        $formatPeso = function($n) {
            return '₱' . number_format($n, 2, '.', ',');
        };
    @endphp
    
    <!-- Debug Info -->
    <div style="background: #f0f8ff; border: 1px solid #b0d4f1; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
        <h4 style="color: #1a5490; margin: 0 0 10px 0;">Debug Information</h4>
        <p style="margin: 5px 0; color: #333;">Total Purchases in Database: <strong>{{ $purchaseCount }}</strong></p>
        <p style="margin: 5px 0; color: #333;">Total Revenue: <strong>{{ $formatPeso($totalRevenue) }}</strong></p>
        @if($purchaseCount > 0)
            <p style="margin: 5px 0; color: #333;">Latest Purchase: <strong>{{ $allPurchases->last()->created_at ?? 'N/A' }}</strong></p>
        @endif
    </div>

    <!-- Time Range Toggles -->
    <div style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        <div style="display: flex; gap: 10px;">
            <button onclick="showChart('daily')" id="dailyBtn" style="flex: 1; padding: 12px 24px; background: #1b3a1b; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer;">Daily</button>
            <button onclick="alert('Weekly view coming soon')" id="weeklyBtn" style="flex: 1; padding: 12px 24px; background: #f0f0f0; color: #666; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer;">Weekly</button>
            <button onclick="alert('Monthly view coming soon')" id="monthlyBtn" style="flex: 1; padding: 12px 24px; background: #f0f0f0; color: #666; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer;">Monthly</button>
            <button onclick="alert('Yearly view coming soon')" id="yearlyBtn" style="flex: 1; padding: 12px 24px; background: #f0f0f0; color: #666; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer;">Yearly</button>
        </div>
    </div>

    <!-- Chart Section -->
    <div style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="color: #1b3a1b; font-size: 18px; margin-bottom: 20px;">
            Daily Sales (Last 7 Days)
        </h3>
        <div style="height: 400px; position: relative;">
            @if($purchaseCount > 0)
                <canvas id="salesChart" style="width: 100%; height: 100%;"></canvas>
            @else
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: #aaa;">
                    <p style="margin: 0; font-size: 16px;">No sales data yet</p>
                    <p style="margin: 5px 0; font-size: 13px;">Complete a sale in POS to begin</p>
                    <button onclick="createSamplePurchase()" style="background: #1b3a1b; color: white; border: none; padding: 8px 16px; border-radius: 6px; margin-top: 10px; cursor: pointer;">Create Sample Purchase</button>
                </div>
            @endif
        </div>
    </div>

    <!-- Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
        <div style="background: linear-gradient(135deg, #1b3a1b 0%, #2d5a2d 100%); color: white; padding: 25px; border-radius: 12px;">
            <h4 style="margin: 0; font-size: 16px; margin-bottom: 15px;">Total Revenue</h4>
            <div style="font-size: 32px; font-weight: bold;">{{ $formatPeso($totalRevenue) }}</div>
        </div>
        <div style="background: linear-gradient(135deg, #1b3a1b 0%, #2d5a2d 100%); color: white; padding: 25px; border-radius: 12px;">
            <h4 style="margin: 0; font-size: 16px; margin-bottom: 15px;">Average Sales</h4>
            <div style="font-size: 32px; font-weight: bold;">{{ $formatPeso($averageSales) }}</div>
        </div>
        <div style="background: linear-gradient(135deg, #1b3a1b 0%, #2d5a2d 100%); color: white; padding: 25px; border-radius: 12px;">
            <h4 style="margin: 0; font-size: 16px; margin-bottom: 15px;">Peak Sales</h4>
            <div style="font-size: 32px; font-weight: bold;">{{ $formatPeso($peakSales) }}</div>
        </div>
    </div>
</div>

@if($purchaseCount > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart data from backend
const chartData = @json($dailyData);

// Create chart
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: chartData.map(d => d.label),
        datasets: [{
            label: 'Daily Sales',
            data: chartData.map(d => d.value),
            borderColor: '#1b3a1b',
            backgroundColor: 'rgba(27, 58, 27, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#1b3a1b',
            pointBorderColor: '#1b3a1b',
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'white',
                borderColor: '#1b3a1b',
                borderWidth: 1.5,
                titleColor: '#333',
                bodyColor: '#1b3a1b',
                padding: 12,
                displayColors: false,
                callbacks: {
                    label: function(context) {
                        return '₱' + context.parsed.y.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#666',
                    font: {
                        size: 12
                    }
                }
            },
            y: {
                grid: {
                    color: '#e5e5e5',
                    borderDash: [3, 3]
                },
                ticks: {
                    color: '#666',
                    font: {
                        size: 12
                    },
                    callback: function(value) {
                        return '₱' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

function showChart(period) {
    console.log('Chart view:', period);
    // TODO: Implement other periods
}
</script>
@else
<script>
function createSamplePurchase() {
    fetch('/api/create-sample-data')
        .then(response => response.json())
        .then(data => {
            console.log('Sample data created:', data);
            alert('Sample purchase created! Refresh the page to see analytics.');
            location.reload();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error creating sample purchase');
        });
}
</script>
@endif
@endsection
