@extends('layouts.app')

@section('content')
<div style="max-width: 1400px; margin: 30px auto; padding: 0 20px; display: flex; flex-direction: column; gap: 20px;">
    
    <!-- Header Section -->
    <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 30px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="color: #1b3a1b; font-size: 28px; font-weight: bold; margin: 0;">Sales Analytics Dashboard</h1>
            <p style="color: #666; font-size: 14px; margin: 10px 0 0;">Comprehensive sales analytics and reporting for your cooperative</p>
        </div>
        
        <!-- Filter Controls -->
        <div style="display: flex; gap: 15px; align-items: center;">
            <form method="GET" action="{{ route('sales-analytics.index') }}" style="display: flex; gap: 10px;">
                <select name="period" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    <option value="weekly" {{ request('period') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                    <option value="monthly" {{ request('period') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                    <option value="yearly" {{ request('period') == 'yearly' ? 'selected' : '' }}>Yearly</option>
                </select>
                
                <select name="member_id" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    <option value="">All Members</option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}" {{ request('member_id') == $member->id ? 'selected' : '' }}>
                            {{ $member->first_name }} {{ $member->last_name }} ({{ $member->member_number }})
                        </option>
                    @endforeach
                </select>
                
                <button type="submit" style="background: #1b3a1b; color: #fff; padding: 8px 20px; border: none; border-radius: 4px; font-size: 14px; cursor: pointer;">
                    Apply Filters
                </button>
            </form>
            
            <a href="{{ route('sales-analytics.print', ['period' => request('period', 'monthly'), 'member_id' => request('member_id')]) }}" target="_blank" style="background: #d0ff00; color: #1b3a1b; padding: 8px 20px; border: none; border-radius: 4px; font-size: 14px; text-decoration: none; display: inline-block;">
                <i class="fas fa-print"></i> Print Report
            </a>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div style="display: flex; gap: 20px; margin-bottom: 20px;">
        <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 25px; flex: 1; text-align: center;">
            <div style="font-size: 24px; font-weight: bold; color: #1b3a1b;">₱{{ number_format($analytics['total_sales'], 2) }}</div>
            <div style="font-size: 14px; color: #666; margin-top: 8px;">Total Sales</div>
        </div>
        
        <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 25px; flex: 1; text-align: center;">
            <div style="font-size: 24px; font-weight: bold; color: #1b3a1b;">{{ $analytics['total_transactions'] }}</div>
            <div style="font-size: 14px; color: #666; margin-top: 8px;">Total Transactions</div>
        </div>
        
        <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 25px; flex: 1; text-align: center;">
            <div style="font-size: 24px; font-weight: bold; color: #1b3a1b;">₱{{ number_format($analytics['average_transaction'], 2) }}</div>
            <div style="font-size: 14px; color: #666; margin-top: 8px;">Average Transaction</div>
        </div>
    </div>
    
    <!-- Charts Section -->
    <div style="display: flex; gap: 20px;">
        <!-- Period Chart -->
        <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 25px; flex: 2;">
            <h3 style="color: #1b3a1b; font-size: 18px; margin: 0 0 15px;">Sales Trend - {{ ucfirst(request('period')) }}</h3>
            <div style="height: 300px; position: relative;">
                <canvas id="salesChart" style="width: 100%; height: 100%;"></canvas>
            </div>
        </div>
        
        <!-- Top Products -->
        <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 25px; flex: 1;">
            <h3 style="color: #1b3a1b; font-size: 18px; margin: 0 0 15px;">Top Selling Products</h3>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                    <thead>
                        <tr style="background: #1b3a1b; color: #fff;">
                            <th style="padding: 12px; text-align: left;">Product</th>
                            <th style="padding: 12px; text-align: right;">Units Sold</th>
                            <th style="padding: 12px; text-align: right;">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($analytics['top_products'] as $product)
                            <tr style="border-bottom: 1px solid #f0f0f0;">
                                <td style="padding: 12px;">{{ $product['product_name'] }}</td>
                                <td style="padding: 12px; text-align: right;">{{ $product['total_sold'] }}</td>
                                <td style="padding: 12px; text-align: right;">₱{{ number_format($product['total_revenue'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Member Analytics Section -->
    @if(count($analytics['member_analytics']) > 0)
        <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 25px;">
            <h3 style="color: #1b3a1b; font-size: 18px; margin: 0 0 15px;">Member Analytics - Per Member Breakdown</h3>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                    <thead>
                        <tr style="background: #1b3a1b; color: #fff;">
                            <th style="padding: 12px; text-align: left;">Member</th>
                            <th style="padding: 12px; text-align: right;">Total Purchases</th>
                            <th style="padding: 12px; text-align: right;">Transactions</th>
                            <th style="padding: 12px; text-align: right;">Average</th>
                            <th style="padding: 12px; text-align: left;">Last Purchase</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($analytics['member_analytics'] as $memberId => $memberData)
                            <tr style="border-bottom: 1px solid #f0f0f0;">
                                <td style="padding: 12px;">
                                    <div>
                                        <div style="font-weight: bold;">{{ $memberData['member_name'] }}</div>
                                        <div style="font-size: 12px; color: #666;">#{{ $memberData['member_number'] }}</div>
                                    </div>
                                </td>
                                <td style="padding: 12px; text-align: right;">₱{{ number_format($memberData['total_purchases'], 2) }}</td>
                                <td style="padding: 12px; text-align: right;">{{ $memberData['transaction_count'] }}</td>
                                <td style="padding: 12px; text-align: right;">₱{{ number_format($memberData['average_purchase'], 2) }}</td>
                                <td style="padding: 12px;">{{ $memberData['last_purchase'] ? $memberData['last_purchase']->format('M d, Y') : 'Never' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const periodData = @json($analytics['period_data']);
    const period = '{{ request('period') }}';
    
    // Create chart
    const ctx = document.getElementById('salesChart').getContext('2d');
    const labels = periodData.map(item => item.period);
    const salesData = periodData.map(item => item.total_sales);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Sales',
                data: salesData,
                borderColor: '#1b3a1b',
                backgroundColor: 'rgba(27, 58, 27, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ₱' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>
@endsection
