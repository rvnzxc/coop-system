@extends('layouts.app')

@section('title', 'Credits')

@section('content')
<div class="mx-auto max-w-7xl">
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            <i class="fa fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
        <div class="min-w-0">
            <h2 class="text-xl font-bold text-slate-900">Credit Management</h2>
            <p class="mt-0.5 text-sm text-slate-500">Track and collect member credit payments</p>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-base text-amber-600">
                <i class="fa fa-credit-card"></i>
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">₱{{ number_format($totalOutstanding, 2) }}</div>
                <div class="text-xs text-slate-500">Total Outstanding</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-base text-red-600">
                <i class="fa fa-exclamation-circle"></i>
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">{{ $unpaidCount }}</div>
                <div class="text-xs text-slate-500">Unpaid Credits</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-yellow-50 text-base text-yellow-600">
                <i class="fa fa-clock-o"></i>
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">{{ $partialCount }}</div>
                <div class="text-xs text-slate-500">Partial Payments</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-base text-blue-600">
                <i class="fa fa-users"></i>
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">{{ $membersWithDebt }}</div>
                <div class="text-xs text-slate-500">Members with Debt</div>
            </div>
        </div>
    </div>

    {{-- Filter tabs --}}
    <div class="mb-4 flex gap-2">
        <a href="{{ route('credits.index') }}" class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors {{ !$status ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">All</a>
        <a href="{{ route('credits.index', ['status' => 'unpaid']) }}" class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors {{ $status === 'unpaid' ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Unpaid</a>
        <a href="{{ route('credits.index', ['status' => 'partial']) }}" class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors {{ $status === 'partial' ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Partial</a>
        <a href="{{ route('credits.index', ['status' => 'paid']) }}" class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors {{ $status === 'paid' ? 'bg-brand-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">Paid</a>
    </div>

    {{-- Credits grouped by member --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="max-h-[calc(100vh-24rem)] overflow-auto">
            <table class="w-full text-left text-sm">
                <thead class="sticky top-0 z-10 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5 font-semibold">Member</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Total Credit</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Total Paid</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Balance</th>
                        <th class="px-5 py-3.5 text-center font-semibold">Credits</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($groupedCredits as $memberId => $group)
                    {{-- Member row --}}
                    <tr class="cursor-pointer transition-colors hover:bg-slate-50 {{ $group['has_unpaid'] ? 'bg-amber-50/30' : '' }}" onclick="toggleMember({{ $memberId }})">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <i id="chevron-{{ $memberId }}" class="fa fa-chevron-right text-xs text-slate-400 transition-transform"></i>
                                <div>
                                    <div class="font-medium text-slate-900">{{ $group['member']->first_name }} {{ $group['member']->last_name }}</div>
                                    <div class="text-xs text-slate-400">{{ $group['member']->member_number }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-right font-semibold text-slate-700">₱{{ number_format($group['total'], 2) }}</td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-right text-slate-600">₱{{ number_format($group['total_paid'], 2) }}</td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-right font-bold {{ $group['balance'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">₱{{ number_format($group['balance'], 2) }}</td>
                        <td class="px-5 py-3.5 text-center">
                            <span class="inline-flex items-center justify-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">{{ $group['credits']->count() }}</span>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-right">
                            @if($group['has_unpaid'])
                            <button onclick="event.stopPropagation(); openPayMemberModal({{ $memberId }}, '{{ $group['member']->first_name }} {{ $group['member']->last_name }}', {{ $group['balance'] }})" class="cursor-pointer rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-brand-700">
                                <i class="fa fa-money"></i> Pay
                            </button>
                            @endif
                        </td>
                    </tr>

                    {{-- Expandable credit details --}}
                    <tr id="member-credits-{{ $memberId }}" style="display: none;">
                        <td colspan="6" class="bg-slate-50 px-5 py-0">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-slate-400">
                                        <th class="py-2.5 text-left font-medium">Date</th>
                                        <th class="py-2.5 text-left font-medium">Items</th>
                                        <th class="py-2.5 text-right font-medium">Amount</th>
                                        <th class="py-2.5 text-right font-medium">Paid</th>
                                        <th class="py-2.5 text-right font-medium">Balance</th>
                                        <th class="py-2.5 text-center font-medium">Status</th>
                                        <th class="py-2.5 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach($group['credits'] as $credit)
                                    <tr class="hover:bg-white">
                                        <td class="py-2 text-slate-600">{{ $credit->created_at->format('M d, Y') }}</td>
                                        <td class="py-2">
                                            @if($credit->items_snapshot)
                                                @foreach($credit->items_snapshot as $item)
                                                    <span class="mr-1 inline-block rounded bg-slate-200 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">{{ $item['product_name'] }} x{{ $item['quantity'] }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap py-2 text-right font-medium text-slate-700">₱{{ number_format($credit->amount, 2) }}</td>
                                        <td class="whitespace-nowrap py-2 text-right text-slate-600">₱{{ number_format($credit->amount_paid, 2) }}</td>
                                        <td class="whitespace-nowrap py-2 text-right font-semibold {{ $credit->balance > 0 ? 'text-red-600' : 'text-emerald-600' }}">₱{{ number_format($credit->balance, 2) }}</td>
                                        <td class="py-2 text-center">
                                            @if($credit->status === 'paid')
                                                <span class="inline-flex items-center gap-0.5 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700">Paid</span>
                                            @elseif($credit->status === 'partial')
                                                <span class="inline-flex items-center gap-0.5 rounded-full bg-yellow-50 px-2 py-0.5 text-[10px] font-medium text-yellow-700">Partial</span>
                                            @else
                                                <span class="inline-flex items-center gap-0.5 rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-medium text-red-700">Unpaid</span>
                                            @endif
                                        </td>
                                        <td class="py-2 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                @if($credit->status !== 'paid')
                                                <button onclick="event.stopPropagation(); openPayModal({{ $credit->id }}, '{{ $group['member']->first_name }} {{ $group['member']->last_name }}', {{ $credit->balance }})" class="cursor-pointer rounded bg-brand-600 px-2 py-1 text-[10px] font-semibold text-white hover:bg-brand-700">Pay</button>
                                                @endif
                                                <a href="{{ route('credits.show', $credit->id) }}" onclick="event.stopPropagation()" class="cursor-pointer rounded border border-slate-200 bg-white px-2 py-1 text-[10px] font-semibold text-slate-600 hover:bg-slate-50">View</a>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center">
                                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400">
                                    <i class="fa fa-credit-card"></i>
                                </div>
                                <p class="text-base font-semibold text-slate-700">No credit records found</p>
                                <p class="mt-1 text-sm text-slate-500">Credit sales will appear here.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Pay Modal --}}
<div id="payModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50">
    <div class="mx-4 w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
        <h3 class="mb-1 text-base font-bold text-slate-900">Record Credit Payment</h3>
        <p id="payModalMember" class="mb-4 text-sm text-slate-500"></p>
        <form id="payModalForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="mb-1 block text-xs font-semibold text-slate-600">Payment Amount</label>
                <input type="number" name="amount_paid" id="payModalAmount" step="0.01" min="0.01" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30" required>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closePayModal()" class="flex-1 cursor-pointer rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="flex-1 cursor-pointer rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">Record Payment</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleMember(memberId) {
    const row = document.getElementById('member-credits-' + memberId);
    const chevron = document.getElementById('chevron-' + memberId);
    if (row) {
        const isVisible = row.style.display !== 'none';
        row.style.display = isVisible ? 'none' : '';
        if (chevron) {
            chevron.style.transform = isVisible ? '' : 'rotate(90deg)';
        }
    }
}

function openPayModal(creditId, memberName, balance) {
    document.getElementById('payModalMember').textContent = memberName + ' — Balance: ₱' + balance.toFixed(2);
    document.getElementById('payModalAmount').value = balance.toFixed(2);
    document.getElementById('payModalAmount').max = balance;
    document.getElementById('payModalForm').action = '/credits/' + creditId + '/pay';
    document.getElementById('payModal').style.display = 'flex';
}

function openPayMemberModal(memberId, memberName, balance) {
    // For paying against the first unpaid credit of this member
    // Find the first unpaid credit row in the expanded section
    const memberRow = document.getElementById('member-credits-' + memberId);
    if (memberRow) {
        memberRow.style.display = '';
        const chevron = document.getElementById('chevron-' + memberId);
        if (chevron) chevron.style.transform = 'rotate(90deg)';
    }

    // Collect unpaid credits from this member's section
    const payBtns = memberRow ? memberRow.querySelectorAll('button') : [];
    for (const btn of payBtns) {
        if (btn.textContent.trim() === 'Pay') {
            btn.click();
            return;
        }
    }
}

function closePayModal() {
    document.getElementById('payModal').style.display = 'none';
}

document.getElementById('payModal').addEventListener('click', function(e) {
    if (e.target === this) closePayModal();
});
</script>
@endsection
