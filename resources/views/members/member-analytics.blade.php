@extends('layouts.app')

@section('content')
<div style="max-width: 1200px; margin: 30px auto; padding: 0 20px;">
    
    <!-- Header -->
    <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 30px; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 20px;">
            <!-- Member Avatar -->
            <div style="width: 80px; height: 80px; border-radius: 50%; background: #1b3a1b; display: flex; align-items: center; justify-content: center; color: #d0ff00; font-size: 32px;">
                <i class="fas fa-user"></i>
            </div>
            
            <!-- Member Info -->
            <div style="flex: 1;">
                <h2 style="color: #1b3a1b; font-size: 24px; font-weight: bold; margin: 0 0 10px 0;">
                    {{ $member->first_name }} {{ $member->last_name }}
                </h2>
                <div style="color: #666; font-size: 14px; margin-bottom: 5px;">
                    @if($member->email)
                        <div><i class="fas fa-envelope" style="margin-right: 8px;"></i>{{ $member->email }}</div>
                    @endif
                    @if($member->phone)
                        <div><i class="fas fa-phone" style="margin-right: 8px;"></i>{{ $member->phone }}</div>
                    @endif
                    <div style="margin-top: 8px;">
                        @if($member->is_active)
                            <span style="background: #e8f5e8; color: #2d5a2d; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: bold;">Active</span>
                        @else
                            <span style="background: #ffe8e8; color: #d32f2f; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: bold;">Inactive</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Analytics Dashboard -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        
        <!-- Left Column - Summary Stats -->
        <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 25px;">
            <h3 style="color: #1b3a1b; font-size: 18px; margin: 0 0 15px 0;">Purchase Summary</h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div style="background: #f8f9fa; border-radius: 8px; padding: 20px; text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #1b3a1b;">₱{{ number_format($member->total_purchases, 2) }}</div>
                    <div style="font-size: 12px; color: #666; margin-top: 5px;">Total Purchases</div>
                </div>
                
                <div style="background: #f8f9fa; border-radius: 8px; padding: 20px; text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #1b3a1b;">{{ $member->purchase_count }}</div>
                    <div style="font-size: 12px; color: #666; margin-top: 5px;">Transactions</div>
                </div>
                
                <div style="background: #f8f9fa; border-radius: 8px; padding: 20px; text-align: center;">
                    <div style="font-size: 24px; font-weight: bold; color: #1b3a1b;">₱{{ number_format($member->purchase_count > 0 ? $member->total_purchases / $member->purchase_count : 0, 2) }}</div>
                    <div style="font-size: 12px; color: #666; margin-top: 5px;">Average Purchase</div>
                </div>
            </div>
            
            <div style="background: #f8f9fa; border-radius: 8px; padding: 20px;">
                <div style="font-size: 16px; font-weight: bold; color: #1b3a1b; margin-bottom: 10px;">Member Since</div>
                <div style="font-size: 14px; color: #666;">{{ $member->created_at->format('F d, Y') }}</div>
                <div style="font-size: 14px; color: #666;">Last Purchase</div>
                <div style="font-size: 14px; color: #666;">{{ $member->last_purchase_date ? $member->last_purchase_date->format('F d, Y') : 'Never' }}</div>
            </div>
        </div>
        
        <!-- Right Column - Purchase Analytics -->
        <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 25px;">
            <h3 style="color: #1b3a1b; font-size: 18px; margin: 0 0 15px 0;">Purchase Analytics</h3>
            
            <!-- Period Filter -->
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <label style="font-size: 14px; color: #666; font-weight: bold;">Analytics Period:</label>
                <select id="periodFilter" onchange="filterMemberAnalytics()" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                    <option value="all">All Time</option>
                    <option value="weekly">Last 7 Days</option>
                    <option value="monthly">Last 30 Days</option>
                    <option value="yearly">Last 365 Days</option>
                </select>
                
                <button onclick="window.print()" style="background: #d0ff00; color: #1b3a1b; padding: 8px 16px; border: none; border-radius: 4px; font-size: 14px; cursor: pointer;">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
            
            <!-- Purchase History Table -->
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                    <thead>
                        <tr style="background: #1b3a1b; color: #fff;">
                            <th style="padding: 12px; text-align: left; border: 1px solid #fff;">Date</th>
                            <th style="padding: 12px; text-align: left; border: 1px solid #fff;">Product</th>
                            <th style="padding: 12px; text-align: right; border: 1px solid #fff;">Price</th>
                            <th style="padding: 12px; text-align: left; border: 1px solid #fff;">Total</th>
                        </tr>
                    </thead>
                    <tbody id="purchaseHistoryTable">
                        <!-- Purchase history will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Back Button -->
    <div style="text-align: center; margin-top: 30px;">
        <a href="{{ route('members.index') }}" style="background: #6c757d; color: #fff; padding: 12px 30px; border-radius: 6px; font-weight: bold; font-size: 14px; border: none; text-decoration: none; display: inline-block; transition: background 0.3s ease;" onmouseover="this.style.background='#5a6268'" onmouseout="this.style.background='#6c757d'">
            Back to Members
        </a>
    </div>
</div>

<script>
// Get member purchase history
const memberPurchases = @json($member->purchases ?? []);

function filterMemberAnalytics() {
    const period = document.getElementById('periodFilter').value;
    const tableBody = document.getElementById('purchaseHistoryTable');
    
    // Clear existing rows
    tableBody.innerHTML = '';
    
    // Filter purchases based on period
    let filteredPurchases = memberPurchases;
    
    if (period !== 'all') {
        const now = new Date();
        
        filteredPurchases = memberPurchases.filter(purchase => {
            if (!purchase.purchase_date) return false;
            
            const purchaseDate = new Date(purchase.purchase_date);
            const diffTime = now - purchaseDate;
            const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
            
            switch (period) {
                case 'weekly':
                    return diffDays <= 7;
                case 'monthly':
                    return diffDays <= 30;
                case 'yearly':
                    return diffDays <= 365;
                default:
                    return true;
            }
        });
    }
    
    // Sort by date (newest first)
    filteredPurchases.sort((a, b) => new Date(b.purchase_date) - new Date(a.purchase_date));
    
    // Populate table
    filteredPurchases.forEach(purchase => {
        const row = document.createElement('tr');
        row.style.cssText = 'border-bottom: 1px solid #f0f0f0;';
        
        // Date cell
        const dateCell = document.createElement('td');
        dateCell.style.cssText = 'padding: 12px; text-align: left; width: 25%;';
        dateCell.textContent = purchase.purchase_date ? new Date(purchase.purchase_date).toLocaleDateString() : 'N/A';
        
        // Product cell
        const productCell = document.createElement('td');
        productCell.style.cssText = 'padding: 12px; text-align: left; width: 40%;';
        productCell.textContent = purchase.product_name || 'N/A';
        
        // Price cell
        const priceCell = document.createElement('td');
        priceCell.style.cssText = 'padding: 12px; text-align: right; width: 15%;';
        priceCell.textContent = purchase.amount ? `₱${parseFloat(purchase.amount).toFixed(2)}` : '₱0.00';
        
        // Total cell (same as price for individual purchase)
        const totalCell = document.createElement('td');
        totalCell.style.cssText = 'padding: 12px; text-align: right; width: 20%;';
        totalCell.textContent = purchase.amount ? `₱${parseFloat(purchase.amount).toFixed(2)}` : '₱0.00';
        
        // Append cells to row
        row.appendChild(dateCell);
        row.appendChild(productCell);
        row.appendChild(priceCell);
        row.appendChild(totalCell);
        
        // Append row to table
        tableBody.appendChild(row);
    });
    
    // Update summary
    updatePurchaseSummary(filteredPurchases);
}

function updatePurchaseSummary(purchases) {
    const totalPurchases = purchases.reduce((sum, purchase) => sum + parseFloat(purchase.amount || 0), 0);
    const transactionCount = purchases.length;
    const averagePurchase = transactionCount > 0 ? totalPurchases / transactionCount : 0;
    
    // Update summary display
    const totalElement = document.querySelector('.summary-total-purchases');
    if (totalElement) totalElement.textContent = `₱${totalPurchases.toFixed(2)}`;
    
    const transactionElement = document.querySelector('.summary-transactions');
    if (transactionElement) transactionElement.textContent = transactionCount;
    
    const averageElement = document.querySelector('.summary-average-purchase');
    if (averageElement) averageElement.textContent = `₱${averagePurchase.toFixed(2)}`;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    filterMemberAnalytics();
});
</script>

<style>
.summary-total-purchases, .summary-transactions, .summary-average-purchase {
    transition: all 0.3s ease;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-analytics {
    background: #17a2b8;
    color: #fff;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 5px;
}

.btn-analytics:hover {
    background: #27ae60;
}

@media print {
    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        line-height: 1.4;
        color: #333;
    }
    
    .action-buttons {
        display: none;
    }
    
    .summary-stats {
        page-break-inside: avoid;
    }
    
    table {
        page-break-inside: avoid;
    }
    
    tr {
        page-break-inside: avoid;
    }
}
</style>
@endsection
