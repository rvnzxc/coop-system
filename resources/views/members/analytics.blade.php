@extends('layouts.app')

@section('content')
<div style="max-width: 1400px; margin: 30px auto; padding: 0 20px; display: flex; flex-direction: column; gap: 20px;">
    
    <!-- TOP PROFILE CARD -->
    <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 30px; display: flex; align-items: center; gap: 25px;">
        <!-- Avatar -->
        <div style="width: 80px; height: 80px; border-radius: 50%; background: #1b3a1b; display: flex; align-items: center; justify-content: center; color: #d0ff00; font-size: 32px;">
            <i class="fas fa-user"></i>
        </div>
        
        <!-- Member Info -->
        <div style="flex: 1;">
            <h2 style="color: #1b3a1b; font-size: 26px; font-weight: bold; margin: 0 0 6px 0;">
                {{ $member->first_name }} {{ $member->last_name }}
            </h2>
            @if($member->email)
                <div style="color: #666; font-size: 14px; margin-bottom: 4px;">
                    <i class="fas fa-envelope" style="margin-right: 8px;"></i>{{ $member->email }}
                </div>
            @endif
            @if($member->phone)
                <div style="color: #666; font-size: 14px; margin-bottom: 4px;">
                    <i class="fas fa-phone" style="margin-right: 8px;"></i>{{ $member->phone }}
                </div>
            @endif
            <div style="margin-top: 8px; display: inline-block;">
                @if($member->is_active)
                    <span style="background: #e8f5e8; color: #2d5a2d; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: bold;">Active</span>
                @else
                    <span style="background: #ffe8e8; color: #d32f2f; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: bold;">Inactive</span>
                @endif
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div style="display: flex; gap: 10px; margin-left: auto;">
            <a href="{{ route('members.edit', $member->id) }}" style="background: #2196f3; color: #fff; padding: 8px 20px; border-radius: 6px; font-weight: bold; border: none; font-size: 13px; text-decoration: none; display: inline-block; transition: background 0.3s ease;" onmouseover="this.style.background='#1976d2'" onmouseout="this.style.background='#2196f3'">
                Edit
            </a>
            <a href="{{ route('members.card', $member->id) }}" style="background: #1b3a1b; color: #d0ff00; padding: 8px 20px; border-radius: 6px; font-weight: bold; border: none; font-size: 13px; text-decoration: none; display: inline-block; transition: background 0.3s ease;" onmouseover="this.style.background='#2d5a2d'" onmouseout="this.style.background='#1b3a1b'">
                <i class="fa fa-id-card"></i> Print Card
            </a>
            <form action="{{ route('members.destroy', $member->id) }}" method="POST" style="display: inline-block; margin: 0;">
                @csrf
                @method('DELETE')
                <button type="submit" style="background: #f44336; color: #fff; padding: 8px 20px; border-radius: 6px; font-weight: bold; border: none; font-size: 13px; cursor: pointer; transition: background 0.3s ease;" onmouseover="this.style.background='#d32f2f'" onmouseout="this.style.background='#f44336'" onclick="return confirm('Are you sure you want to delete this member?')">
                    Delete
                </button>
            </form>
        </div>
    </div>
    
    <!-- MIDDLE ROW -->
    <div style="display: flex; gap: 20px;">
        <!-- LEFT CARD - Quick Stats -->
        <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 25px; flex: 1;">
            <div style="font-size: 16px; font-weight: bold; color: #1b3a1b; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0;">
                Quick Stats
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f8f8f8;">
                <span style="color: #666; font-size: 14px;">Member Since</span>
                <span style="color: #333; font-size: 14px; font-weight: bold;">{{ $member->created_at->format('F d, Y') }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f8f8f8;">
                <span style="color: #666; font-size: 14px;">Status</span>
                <span style="color: #333; font-size: 14px; font-weight: bold;">{{ $member->is_active ? 'Active' : 'Inactive' }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f8f8f8;">
                <span style="color: #666; font-size: 14px;">Last Purchase</span>
                <span style="color: #333; font-size: 14px; font-weight: bold;">{{ $lastPurchaseDate ? $lastPurchaseDate->format('F d, Y') : 'Never' }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 10px 0;">
                <span style="color: #666; font-size: 14px;">Total Purchases</span>
                <span style="color: #333; font-size: 14px; font-weight: bold;">₱{{ number_format($member->total_purchases, 2) }}</span>
            </div>
        </div>
        
        <!-- RIGHT CARD - Purchase Analytics -->
        <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 25px; flex: 1;">
            <div style="font-size: 16px; font-weight: bold; color: #1b3a1b; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0;">
                Purchase Analytics
            </div>
            
            <div style="display: flex; gap: 12px;">
                <div style="background: #f8f9fa; border-radius: 8px; padding: 15px; text-align: center; flex: 1;">
                    <div style="font-size: 22px; font-weight: bold; color: #1b3a1b;">₱{{ number_format($totalPurchases, 2) }}</div>
                    <div style="font-size: 12px; color: #666; margin-top: 4px;">Total Purchases</div>
                </div>
                
                <div style="background: #f8f9fa; border-radius: 8px; padding: 15px; text-align: center; flex: 1;">
                    <div style="font-size: 22px; font-weight: bold; color: #1b3a1b;">{{ $purchaseCount }}</div>
                    <div style="font-size: 12px; color: #666; margin-top: 4px;">Transactions</div>
                </div>
                
                <div style="background: #f8f9fa; border-radius: 8px; padding: 15px; text-align: center; flex: 1;">
                    <div style="font-size: 22px; font-weight: bold; color: #1b3a1b;">₱{{ number_format($averagePurchase, 2) }}</div>
                    <div style="font-size: 12px; color: #666; margin-top: 4px;">Average</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- BOTTOM ROW -->
    <div style="display: flex; gap: 20px;">
        <!-- LEFT CARD - Purchase Behavior -->
        <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 25px; flex: 1;">
            <div style="font-size: 16px; font-weight: bold; color: #1b3a1b; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0;">
                Purchase Behavior
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f8f8f8;">
                <span style="color: #666; font-size: 14px;">Frequency</span>
                <span style="color: #333; font-size: 14px; font-weight: bold;">{{ $purchaseCount > 10 ? 'High' : ($purchaseCount > 5 ? 'Medium' : 'Low') }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f8f8f8;">
                <span style="color: #666; font-size: 14px;">Average Value</span>
                <span style="color: #333; font-size: 14px; font-weight: bold;">₱{{ number_format($averagePurchase, 2) }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 10px 0;">
                <span style="color: #666; font-size: 14px;">Last Activity</span>
                <span style="color: #333; font-size: 14px; font-weight: bold;">
                    @if($lastPurchaseDate)
                        {{ $lastPurchaseDate->setTimezone('Asia/Manila')->format('M d, Y h:i A') }}
                        {{-- Debug: {{ $lastPurchaseDate->toDateTimeString() }} --}}
                    @else
                        Never
                    @endif
                </span>
            </div>
        </div>
        
        <!-- RIGHT CARD - Member Status -->
        <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 25px; flex: 1;">
            <div style="font-size: 16px; font-weight: bold; color: #1b3a1b; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0;">
                Member Status
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f8f8f8;">
                <span style="color: #666; font-size: 14px;">Loyalty Level</span>
                <span style="background: #e3f2fd; color: #1976d2; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                    @if($totalPurchases >= 10000)
                        Gold
                    @elseif($totalPurchases >= 5000)
                        Silver
                    @elseif($totalPurchases > 0)
                        Bronze
                    @else
                        New
                    @endif
                </span>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f8f8f8;">
                <span style="color: #666; font-size: 14px;">Account Status</span>
                <span style="color: #333; font-size: 14px; font-weight: bold;">{{ $member->is_active ? 'Active' : 'Inactive' }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 10px 0;">
                <span style="color: #666; font-size: 14px;">Member ID</span>
                <span style="font-family: monospace; font-size: 15px; color: #1b3a1b;">#{{ str_pad($member->id, 5, '0', STR_PAD_LEFT) }}</span>
            </div>
        </div>
    </div>
    
    <!-- MEMBER ANALYTICS SECTION (NEW) -->
    <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 25px; margin-bottom: 20px;">
        <div style="font-size: 18px; font-weight: bold; color: #1b3a1b; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0;">
            <i class="fas fa-chart-line" style="margin-right: 10px;"></i> Member Analytics - Per Member Breakdown
        </div>
        
        <!-- Period Filter -->
        <div style="display: flex; gap: 15px; margin-bottom: 20px; align-items: center;">
            <label style="font-size: 14px; color: #666;">Analytics Period:</label>
            <select id="periodFilter" onchange="filterMemberAnalytics()" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
                <option value="all">All Time</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly</option>
            </select>
            
            <button onclick="window.print()" style="background: #d0ff00; color: #1b3a1b; padding: 8px 16px; border: none; border-radius: 4px; font-size: 14px; cursor: pointer;">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
        
        <!-- Member Analytics Table -->
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <thead>
                    <tr style="background: #1b3a1b; color: #fff;">
                        <th style="padding: 12px; text-align: left; border: 1px solid #fff;">Member</th>
                        <th style="padding: 12px; text-align: right; border: 1px solid #fff;">Total Purchases</th>
                        <th style="padding: 12px; text-align: right; border: 1px solid #fff;">Transactions</th>
                        <th style="padding: 12px; text-align: right; border: 1px solid #fff;">Average Purchase</th>
                        <th style="padding: 12px; text-align: right; border: 1px solid #fff;">Last Purchase</th>
                        <th style="padding: 12px; text-align: right; border: 1px solid #fff;">Purchase Frequency</th>
                        <th style="padding: 12px; text-align: left; border: 1px solid #fff;">Status</th>
                    </tr>
                </thead>
                <tbody id="memberAnalyticsTable">
                    <!-- Member data will be populated by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- RECOMMENDATION CARD (full width) -->
    <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 25px;">
        @if($purchaseCount == 0)
            <div style="display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-lightbulb" style="color: #f39c12; font-size: 20px;"></i>
                <div>
                    <div style="font-weight: bold; color: #333; margin-bottom: 5px;">Recommendation</div>
                    <div style="color: #666; font-size: 14px;">This member hasn't made any purchases yet. Consider sending promotional offers to encourage their first purchase.</div>
                </div>
            </div>
        @elseif($lastPurchaseDate && $lastPurchaseDate->diffInDays(now()) > 30)
            <div style="display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-exclamation-triangle" style="color: #e67e22; font-size: 20px;"></i>
                <div>
                    <div style="font-weight: bold; color: #333; margin-bottom: 5px;">Attention Needed</div>
                    <div style="color: #666; font-size: 14px;">This member hasn't purchased in over 30 days. Consider reaching out with special offers.</div>
                </div>
            </div>
        @else
            <div style="display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-check-circle" style="color: #27ae60; font-size: 20px;"></i>
                <div>
                    <div style="font-weight: bold; color: #333; margin-bottom: 5px;">Great Member!</div>
                    <div style="color: #666; font-size: 14px;">This member is actively purchasing and engaged with your cooperative.</div>
                </div>
            </div>
        @endif
    </div>
    
    <!-- Back Button -->
    <div style="text-align: center;">
        <a href="{{ route('members.index') }}" style="background: #6c757d; color: #fff; padding: 12px 30px; border-radius: 6px; font-weight: bold; font-size: 14px; border: none; text-decoration: none; display: inline-block; transition: background 0.3s ease;" onmouseover="this.style.background='#5a6268'" onmouseout="this.style.background='#6c757d'">
            Back to Members
        </a>
    </div>
</div>

<script>
// Use member analytics data from controller
const memberAnalyticsData = @json($memberAnalytics ?? []);

// Convert to array for easier filtering
const allMembersData = Object.values(memberAnalyticsData);

function filterMemberAnalytics() {
    const period = document.getElementById('periodFilter').value;
    const tableBody = document.getElementById('memberAnalyticsTable');
    
    // Clear existing rows
    tableBody.innerHTML = '';
    
    // Filter members based on period
    let filteredMembers = allMembersData;
    
    if (period !== 'all') {
        const now = new Date();
        
        filteredMembers = allMembersData.filter(member => {
            if (!member.last_purchase_date) return false;
            
            const lastPurchase = new Date(member.last_purchase_date);
            const diffTime = now - lastPurchase;
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
    
    // Sort by total purchases (descending)
    filteredMembers.sort((a, b) => b.total_purchases - a.total_purchases);
    
    // Populate table
    filteredMembers.forEach(member => {
        const row = document.createElement('tr');
        row.style.cssText = 'border-bottom: 1px solid #e9ecef;';
        
        // Member info
        const memberCell = document.createElement('td');
        memberCell.style.cssText = 'padding: 12px;';
        memberCell.style.width = '25%';
        memberCell.innerHTML = `
            <div style="font-weight: bold;">${member.first_name} ${member.last_name}</div>
            <div style="font-size: 12px; color: #666;">${member.member_number}</div>
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
    
    // Update summary info
    const totalMembers = filteredMembers.length;
    const totalPurchases = filteredMembers.reduce((sum, member) => sum + member.total_purchases, 0);
    const totalTransactions = filteredMembers.reduce((sum, member) => sum + member.purchase_count, 0);
    
    // Update summary display (you can add this to the page if needed)
    console.log(`Showing ${totalMembers} members with ${totalTransactions} total transactions`);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    filterMemberAnalytics();
});
</script>
@endsection
