@extends('layouts.app')

@section('content')
<div style="max-width: 1200px; margin: 20px auto; padding: 20px;">
    <!-- Print Header -->
    <div style="text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #1b3a1b;">
        <h1 style="color: #1b3a1b; font-size: 32px; font-weight: bold; margin: 0;">
            CCFMPC Cooperative - Sales Analytics Report
        </h1>
        <div style="font-size: 16px; color: #666; margin-top: 10px;">
            Period: {{ ucfirst($period) }} | 
            @if($memberId)
                Member: {{ $analytics['member_analytics'][$memberId]['member_name'] ?? 'All Members' }}
            @else
                All Members
            @endif
            | 
            Generated: {{ now()->format('F d, Y h:i A') }}
        </div>
    </div>
    
    <!-- Summary Section -->
    <div style="margin-bottom: 30px;">
        <h2 style="color: #1b3a1b; font-size: 20px; margin-bottom: 15px;">Executive Summary</h2>
        <div style="display: flex; gap: 20px; margin-bottom: 20px;">
            <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; flex: 1; text-align: center;">
                <div style="font-size: 24px; font-weight: bold; color: #1b3a1b;">₱{{ number_format($analytics['total_sales'], 2) }}</div>
                <div style="font-size: 14px; color: #666; margin-top: 5px;">Total Sales Revenue</div>
            </div>
            
            <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; flex: 1; text-align: center;">
                <div style="font-size: 24px; font-weight: bold; color: #1b3a1b;">{{ $analytics['total_transactions'] }}</div>
                <div style="font-size: 14px; color: #666; margin-top: 5px;">Total Transactions</div>
            </div>
            
            <div style="background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 8px; padding: 20px; flex: 1; text-align: center;">
                <div style="font-size: 24px; font-weight: bold; color: #1b3a1b;">₱{{ number_format($analytics['average_transaction'], 2) }}</div>
                <div style="font-size: 14px; color: #666; margin-top: 5px;">Average Transaction Value</div>
            </div>
        </div>
    </div>
    
    <!-- Period Breakdown -->
    <div style="margin-bottom: 30px;">
        <h2 style="color: #1b3a1b; font-size: 20px; margin-bottom: 15px;">{{ ucfirst($period) }} Sales Breakdown</h2>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr style="background: #1b3a1b; color: #fff;">
                    <th style="padding: 12px; text-align: left; border: 1px solid #fff;">Period</th>
                    <th style="padding: 12px; text-align: right; border: 1px solid #fff;">Sales</th>
                    <th style="padding: 12px; text-align: right; border: 1px solid #fff;">Transactions</th>
                    <th style="padding: 12px; text-align: right; border: 1px solid #fff;">Average</th>
                </tr>
            </thead>
            <tbody>
                @foreach($analytics['period_data'] as $data)
                    <tr style="border-bottom: 1px solid #e9ecef;">
                        <td style="padding: 12px;">{{ $data['period'] }}</td>
                        <td style="padding: 12px; text-align: right;">₱{{ number_format($data['total_sales'], 2) }}</td>
                        <td style="padding: 12px; text-align: right;">{{ $data['transaction_count'] }}</td>
                        <td style="padding: 12px; text-align: right;">₱{{ number_format($data['transaction_count'] > 0 ? $data['total_sales'] / $data['transaction_count'] : 0, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <!-- Top Products Section -->
    <div style="margin-bottom: 30px;">
        <h2 style="color: #1b3a1b; font-size: 20px; margin-bottom: 15px;">Top Selling Products</h2>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr style="background: #1b3a1b; color: #fff;">
                    <th style="padding: 12px; text-align: left; border: 1px solid #fff;">Rank</th>
                    <th style="padding: 12px; text-align: left; border: 1px solid #fff;">Product</th>
                    <th style="padding: 12px; text-align: right; border: 1px solid #fff;">Units Sold</th>
                    <th style="padding: 12px; text-align: right; border: 1px solid #fff;">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($analytics['top_products'] as $index => $product)
                    <tr style="border-bottom: 1px solid #e9ecef;">
                        <td style="padding: 12px; text-align: center;">{{ $index + 1 }}</td>
                        <td style="padding: 12px;">{{ $product['product_name'] }}</td>
                        <td style="padding: 12px; text-align: right;">{{ $product['total_sold'] }}</td>
                        <td style="padding: 12px; text-align: right;">₱{{ number_format($product['total_revenue'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <!-- Member Analytics Section -->
    @if(count($analytics['member_analytics']) > 0)
        <div style="margin-bottom: 30px;">
            <h2 style="color: #1b3a1b; font-size: 20px; margin-bottom: 15px;">Member Analytics - Per Member Breakdown</h2>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <thead>
                    <tr style="background: #1b3a1b; color: #fff;">
                        <th style="padding: 12px; text-align: left; border: 1px solid #fff;">Member</th>
                        <th style="padding: 12px; text-align: right; border: 1px solid #fff;">Total Purchases</th>
                        <th style="padding: 12px; text-align: right; border: 1px solid #fff;">Transactions</th>
                        <th style="padding: 12px; text-align: right; border: 1px solid #fff;">Average</th>
                        <th style="padding: 12px; text-align: left; border: 1px solid #fff;">Last Purchase</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($analytics['member_analytics'] as $memberId => $memberData)
                        <tr style="border-bottom: 1px solid #e9ecef;">
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
    @endif
    
    <!-- Detailed Transactions -->
    @if(count($purchases) > 0)
        <div style="margin-bottom: 30px;">
            <h2 style="color: #1b3a1b; font-size: 20px; margin-bottom: 15px;">Detailed Transactions</h2>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <thead>
                    <tr style="background: #1b3a1b; color: #fff;">
                        <th style="padding: 12px; text-align: left; border: 1px solid #fff;">Date</th>
                        <th style="padding: 12px; text-align: left; border: 1px solid #fff;">Member</th>
                        <th style="padding: 12px; text-align: left; border: 1px solid #fff;">Product</th>
                        <th style="padding: 12px; text-align: right; border: 1px solid #fff;">Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchases as $purchase)
                        <tr style="border-bottom: 1px solid #e9ecef;">
                            <td style="padding: 12px;">{{ $purchase->created_at }}</td>
                            <td style="padding: 12px;">{{ $purchase->first_name }} {{ $purchase->last_name }}</td>
                            <td style="padding: 12px;">{{ $purchase->product_name }}</td>
                            <td style="padding: 12px; text-align: right;">₱{{ number_format($purchase->price, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    
    <!-- Footer -->
    <div style="margin-top: 50px; padding-top: 20px; border-top: 1px solid #1b3a1b; text-align: center; font-size: 12px; color: #666;">
        <p>Report generated on {{ now()->format('F d, Y h:i:s A') }} | CCFMPC Cooperative Management System</p>
    </div>
</div>

<style media="print">
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        line-height: 1.4;
        color: #333;
    }
    
    .no-print {
        display: none !important;
    }
    
    table {
        page-break-inside: avoid;
    }
    
    tr {
        page-break-inside: avoid;
    }
    
    h1, h2 {
        page-break-after: avoid;
    }
    
    @media print {
        body {
            margin: 0;
            padding: 15px;
        }
        
        div[style*="margin-bottom: 30px;"] {
            margin-bottom: 20px !important;
        }
        
        div[style*="margin-bottom: 20px;"] {
            margin-bottom: 15px !important;
        }
        
        div[style*="margin-bottom: 15px;"] {
            margin-bottom: 10px !important;
        }
    }
</style>

<script>
window.onload = function() {
    // Populate member analytics table
    const allMembersData = @json(App\Models\Member::withCount(['purchases as purchase_count'])->withSum('purchases.price as total_purchases')->get());

    function populateMemberAnalyticsTable() {
        const tableBody = document.getElementById('memberAnalyticsTable');
        if (!tableBody) return;
        
        // Clear existing rows
        tableBody.innerHTML = '';
        
        // Get all members and populate table
        allMembersData.forEach(member => {
            const row = document.createElement('tr');
            row.style.cssText = 'border-bottom: 1px solid #e9ecef;';
            
            // Member info cell
            const memberCell = document.createElement('td');
            memberCell.style.cssText = 'padding: 12px;';
            memberCell.style.width = '25%';
            memberCell.innerHTML = `
                <div style="font-weight: bold;">${member.first_name} ${member.last_name}</div>
                <div style="font-size: 12px; color: #666;">#${member.member_number}</div>
            `;
            
            // Stats cells
            const totalPurchasesCell = document.createElement('td');
            totalPurchasesCell.style.cssText = 'padding: 12px; text-align: right; width: 15%;';
            totalPurchasesCell.textContent = `₱${member.total_purchases.toLocaleString()}`;
            
            const transactionCountCell = document.createElement('td');
            transactionCountCell.style.cssText = 'padding: 12px; text-align: right; width: 15%;';
            transactionCountCell.textContent = member.purchase_count;
            
            const averagePurchaseCell = document.createElement('td');
            averagePurchaseCell.style.cssText = 'padding: 12px; text-align: right; width: 15%;';
            averagePurchaseCell.textContent = member.purchase_count > 0 ? `₱${(member.total_purchases / member.purchase_count).toLocaleString()}` : '₱0';
            
            const lastPurchaseCell = document.createElement('td');
            lastPurchaseCell.style.cssText = 'padding: 12px; width: 20%;';
            lastPurchaseCell.textContent = member.last_purchase_date ? new Date(member.last_purchase_date).toLocaleDateString() : 'Never';
            
            const frequencyCell = document.createElement('td');
            frequencyCell.style.cssText = 'padding: 12px; text-align: left; width: 10%;';
            
            // Calculate frequency
            const now = new Date();
            const lastPurchase = member.last_purchase_date ? new Date(member.last_purchase_date) : null;
            if (lastPurchase) {
                const diffTime = now - lastPurchase;
                const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                
                if (diffDays <= 7) {
                    frequencyCell.innerHTML = '<span style="color: #27ae60;">● Weekly</span>';
                } else if (diffDays <= 30) {
                    frequencyCell.innerHTML = '<span style="color: #f59e0b;">● Monthly</span>';
                } else if (diffDays <= 90) {
                    frequencyCell.innerHTML = '<span style="color: #e67e22;">● Quarterly</span>';
                } else {
                    frequencyCell.innerHTML = '<span style="color: #e74c3c;">● Yearly</span>';
                }
            } else {
                frequencyCell.innerHTML = '<span style="color: #666;">Never</span>';
            }
            
            const statusCell = document.createElement('td');
            statusCell.style.cssText = 'padding: 12px; text-align: left; width: 15%;';
            statusCell.innerHTML = member.is_active ? 
                '<span style="background: #e8f5e8; color: #2d5a2d; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: bold;">Active</span>' : 
                '<span style="background: #ffe8e8; color: #d32f2f; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: bold;">Inactive</span>';
            
            // Append cells to row
            row.appendChild(memberCell);
            row.appendChild(totalPurchasesCell);
            row.appendChild(transactionCountCell);
            row.appendChild(averagePurchaseCell);
            row.appendChild(lastPurchaseCell);
            row.appendChild(frequencyCell);
            row.appendChild(statusCell);
            
            // Append row to table
            tableBody.appendChild(row);
        });
    }
    
    // Initialize table on page load
    populateMemberAnalyticsTable();
    
    window.print();
}
</script>
@endsection
