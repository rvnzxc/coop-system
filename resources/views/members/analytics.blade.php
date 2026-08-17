@extends('layouts.app')

@section('title', 'Member Analytics')

@section('content')
<div class="mx-auto flex max-w-6xl flex-col gap-6">

    {{-- TOP PROFILE CARD --}}
    <div class="flex flex-col gap-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center">
        <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-brand-600 text-3xl text-white">
            <i class="fa fa-user"></i>
        </div>

        <div class="min-w-0 flex-1">
            <h2 class="truncate text-2xl font-bold text-slate-900">
                {{ $member->first_name }} {{ $member->last_name }}
            </h2>
            @if($member->email)
                <div class="mt-1 text-sm text-slate-500">
                    <i class="fa fa-envelope mr-2"></i>{{ $member->email }}
                </div>
            @endif
            @if($member->phone)
                <div class="mt-1 text-sm text-slate-500">
                    <i class="fa fa-phone mr-2"></i>{{ $member->phone }}
                </div>
            @endif
            <div class="mt-3">
                @if($member->is_active)
                    <x-badge color="green"><i class="fa fa-circle text-[6px]"></i> Active</x-badge>
                @else
                    <x-badge color="red"><i class="fa fa-circle text-[6px]"></i> Inactive</x-badge>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
            <x-button href="{{ route('members.edit', $member->id) }}" color="secondary" size="sm">
                <i class="fa fa-edit"></i> Edit
            </x-button>
            <x-button href="{{ route('members.card', $member->id) }}" color="primary" size="sm">
                <i class="fa fa-id-card"></i> Print Card
            </x-button>
            <form action="{{ route('members.destroy', $member->id) }}" method="POST">
                @csrf
                @method('DELETE')
                <x-button type="submit" color="danger" size="sm" onclick="return confirm('Are you sure you want to delete this member?')">
                    <i class="fa fa-trash"></i> Delete
                </x-button>
            </form>
        </div>
    </div>

    {{-- MIDDLE ROW --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="Quick Stats">
            <dl class="divide-y divide-slate-100">
                <div class="flex items-center justify-between py-3 first:pt-0 last:pb-0">
                    <dt class="text-sm text-slate-500">Member Since</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ $member->created_at->format('F d, Y') }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-slate-500">Status</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ $member->is_active ? 'Active' : 'Inactive' }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-slate-500">Last Purchase</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ $lastPurchaseDate ? $lastPurchaseDate->format('F d, Y') : 'Never' }}</dd>
                </div>
                <div class="flex items-center justify-between py-3 last:pb-0">
                    <dt class="text-sm text-slate-500">Total Purchases</dt>
                    <dd class="text-sm font-semibold text-slate-900">₱{{ number_format($member->total_purchases, 2) }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card title="Purchase Analytics">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-lg bg-slate-50 p-4 text-center">
                    <div class="text-xl font-bold text-slate-900">₱{{ number_format($totalPurchases, 2) }}</div>
                    <div class="mt-1 text-xs text-slate-500">Total Purchases</div>
                </div>
                <div class="rounded-lg bg-slate-50 p-4 text-center">
                    <div class="text-xl font-bold text-slate-900">{{ $purchaseCount }}</div>
                    <div class="mt-1 text-xs text-slate-500">Transactions</div>
                </div>
                <div class="rounded-lg bg-slate-50 p-4 text-center">
                    <div class="text-xl font-bold text-slate-900">₱{{ number_format($averagePurchase, 2) }}</div>
                    <div class="mt-1 text-xs text-slate-500">Average</div>
                </div>
            </div>
        </x-card>
    </div>

    {{-- BOTTOM ROW --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="Purchase Behavior">
            <dl class="divide-y divide-slate-100">
                <div class="flex items-center justify-between py-3 first:pt-0">
                    <dt class="text-sm text-slate-500">Frequency</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ $purchaseCount > 10 ? 'High' : ($purchaseCount > 5 ? 'Medium' : 'Low') }}</dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-slate-500">Average Value</dt>
                    <dd class="text-sm font-semibold text-slate-900">₱{{ number_format($averagePurchase, 2) }}</dd>
                </div>
                <div class="flex items-center justify-between py-3 last:pb-0">
                    <dt class="text-sm text-slate-500">Last Activity</dt>
                    <dd class="text-sm font-semibold text-slate-900">
                        @if($lastPurchaseDate)
                            {{ $lastPurchaseDate->setTimezone('Asia/Manila')->format('M d, Y h:i A') }}
                        @else
                            Never
                        @endif
                    </dd>
                </div>
            </dl>
        </x-card>

        <x-card title="Member Status">
            <dl class="divide-y divide-slate-100">
                <div class="flex items-center justify-between py-3 first:pt-0">
                    <dt class="text-sm text-slate-500">Loyalty Level</dt>
                    <dd>
                        @if($totalPurchases >= 10000)
                            <x-badge color="amber">Gold</x-badge>
                        @elseif($totalPurchases >= 5000)
                            <x-badge color="gray">Silver</x-badge>
                        @elseif($totalPurchases > 0)
                            <x-badge color="teal">Bronze</x-badge>
                        @else
                            <x-badge color="blue">New</x-badge>
                        @endif
                    </dd>
                </div>
                <div class="flex items-center justify-between py-3">
                    <dt class="text-sm text-slate-500">Account Status</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ $member->is_active ? 'Active' : 'Inactive' }}</dd>
                </div>
                <div class="flex items-center justify-between py-3 last:pb-0">
                    <dt class="text-sm text-slate-500">Member ID</dt>
                    <dd class="font-mono text-sm font-semibold text-slate-900">#{{ str_pad($member->id, 5, '0', STR_PAD_LEFT) }}</dd>
                </div>
            </dl>
        </x-card>
    </div>

    {{-- MEMBER ANALYTICS SECTION --}}
    <x-card>
        <h3 class="mb-4 flex items-center gap-2 text-lg font-bold text-slate-900">
            <i class="fa fa-line-chart text-brand-600"></i> Member Analytics - Per Member Breakdown
        </h3>

        <div class="mb-4 flex flex-wrap items-center gap-4">
            <label class="text-sm text-slate-500">Analytics Period:</label>
            <select id="periodFilter" onchange="filterMemberAnalytics()" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                <option value="all">All Time</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly</option>
            </select>
            <x-button color="secondary" size="sm" onclick="window.print()">
                <i class="fa fa-print"></i> Print Report
            </x-button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-4 py-3 font-semibold">Member</th>
                        <th class="px-4 py-3 text-right font-semibold">Total Purchases</th>
                        <th class="px-4 py-3 text-right font-semibold">Transactions</th>
                        <th class="px-4 py-3 text-right font-semibold">Average Purchase</th>
                        <th class="px-4 py-3 text-right font-semibold">Last Purchase</th>
                        <th class="px-4 py-3 font-semibold">Purchase Frequency</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody id="memberAnalyticsTable" class="divide-y divide-slate-100">
                    <!-- Member data will be populated by JavaScript -->
                </tbody>
            </table>
        </div>
    </x-card>

    {{-- RECOMMENDATION CARD --}}
    <x-card>
        @if($purchaseCount == 0)
            <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600"><i class="fa fa-lightbulb-o"></i></div>
                <div>
                    <div class="font-semibold text-slate-900">Recommendation</div>
                    <div class="mt-1 text-sm text-slate-500">This member hasn't made any purchases yet. Consider sending promotional offers to encourage their first purchase.</div>
                </div>
            </div>
        @elseif($lastPurchaseDate && $lastPurchaseDate->diffInDays(now()) > 30)
            <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-orange-100 text-orange-600"><i class="fa fa-exclamation-triangle"></i></div>
                <div>
                    <div class="font-semibold text-slate-900">Attention Needed</div>
                    <div class="mt-1 text-sm text-slate-500">This member hasn't purchased in over 30 days. Consider reaching out with special offers.</div>
                </div>
            </div>
        @else
            <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600"><i class="fa fa-check-circle"></i></div>
                <div>
                    <div class="font-semibold text-slate-900">Great Member!</div>
                    <div class="mt-1 text-sm text-slate-500">This member is actively purchasing and engaged with your cooperative.</div>
                </div>
            </div>
        @endif
    </x-card>

    <div class="text-center">
        <x-button href="{{ route('members.index') }}" color="secondary">
            <i class="fa fa-arrow-left"></i> Back to Members
        </x-button>
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
        row.className = 'transition-colors hover:bg-slate-50';
        
        // Member info
        const memberCell = document.createElement('td');
        memberCell.className = 'px-4 py-3 w-1/4';
        memberCell.innerHTML = `
            <div class="font-medium text-slate-900">${member.first_name} ${member.last_name}</div>
            <div class="text-xs text-slate-500">${member.member_number}</div>
        `;
        
        // Stats cells
        const totalPurchasesCell = document.createElement('td');
        totalPurchasesCell.className = 'px-4 py-3 text-right w-[15%]';
        totalPurchasesCell.textContent = `₱${member.total_purchases.toLocaleString()}`;
        
        const transactionCountCell = document.createElement('td');
        transactionCountCell.className = 'px-4 py-3 text-right w-[15%]';
        transactionCountCell.textContent = member.purchase_count;
        
        const averagePurchaseCell = document.createElement('td');
        averagePurchaseCell.className = 'px-4 py-3 text-right w-[15%]';
        averagePurchaseCell.textContent = member.purchase_count > 0 ? `₱${(member.total_purchases / member.purchase_count).toLocaleString()}` : '₱0';
        
        const lastPurchaseCell = document.createElement('td');
        lastPurchaseCell.className = 'px-4 py-3 text-right w-[20%]';
        lastPurchaseCell.textContent = member.last_purchase_date ? new Date(member.last_purchase_date).toLocaleDateString() : 'Never';
        
        const frequencyCell = document.createElement('td');
        frequencyCell.className = 'px-4 py-3 w-[10%]';
        
        // Calculate frequency
        const now = new Date();
        const lastPurchase = member.last_purchase_date ? new Date(member.last_purchase_date) : null;
        if (lastPurchase) {
            const diffTime = now - lastPurchase;
            const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays <= 7) {
                frequencyCell.innerHTML = '<span class="text-emerald-600"><i class="fa fa-circle text-[6px]"></i> Weekly</span>';
            } else if (diffDays <= 30) {
                frequencyCell.innerHTML = '<span class="text-amber-500"><i class="fa fa-circle text-[6px]"></i> Monthly</span>';
            } else if (diffDays <= 90) {
                frequencyCell.innerHTML = '<span class="text-orange-500"><i class="fa fa-circle text-[6px]"></i> Quarterly</span>';
            } else {
                frequencyCell.innerHTML = '<span class="text-red-500"><i class="fa fa-circle text-[6px]"></i> Yearly</span>';
            }
        } else {
            frequencyCell.innerHTML = '<span class="text-slate-500">Never</span>';
        }
        
        const statusCell = document.createElement('td');
        statusCell.className = 'px-4 py-3 w-[15%]';
        statusCell.innerHTML = member.is_active ? 
            '<span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20"><i class="fa fa-circle text-[6px]"></i> Active</span>' : 
            '<span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20"><i class="fa fa-circle text-[6px]"></i> Inactive</span>';
        
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
    const totalTransactions = filteredMembers.reduce((sum, member) => sum + member.purchase_count, 0);
    
    console.log(`Showing ${totalMembers} members with ${totalTransactions} total transactions`);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    filterMemberAnalytics();
});
</script>
@endsection
