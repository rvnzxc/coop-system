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

    {{-- Credits table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="max-h-[calc(100vh-24rem)] overflow-auto">
            <table class="w-full text-left text-sm">
                <thead class="sticky top-0 z-10 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5 font-semibold">Member</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Credit Amount</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Paid</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Balance</th>
                        <th class="px-5 py-3.5 font-semibold">Status</th>
                        <th class="px-5 py-3.5 font-semibold">Date</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($credits as $credit)
                    <tr class="transition-colors hover:bg-slate-50 cursor-pointer" data-credit-id="{{ $credit->id }}" data-member-id="{{ $credit->member_id }}" onclick="toggleItems({{ $credit->id }})">
                        <td class="px-5 py-3.5">
                            <div class="font-medium text-slate-900">{{ $credit->member->first_name }} {{ $credit->member->last_name }}</div>
                            <div class="text-xs text-slate-400">{{ $credit->member->member_number }}</div>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-right font-semibold text-slate-700">₱{{ number_format($credit->amount, 2) }}</td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-right text-slate-600">₱{{ number_format($credit->amount_paid, 2) }}</td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-right font-semibold text-slate-900">₱{{ number_format($credit->balance, 2) }}</td>
                        <td class="px-5 py-3.5">
                            @if($credit->status === 'paid')
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700"><i class="fa fa-check-circle"></i> Paid</span>
                            @elseif($credit->status === 'partial')
                                <span class="inline-flex items-center gap-1 rounded-full bg-yellow-50 px-2.5 py-0.5 text-xs font-medium text-yellow-700"><i class="fa fa-clock-o"></i> Partial</span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700"><i class="fa fa-exclamation-circle"></i> Unpaid</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-slate-500">{{ $credit->created_at->format('M d, Y') }}</td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-right">
                            <div class="flex items-center justify-end gap-1.5">
                                @if($credit->status !== 'paid')
                                <button onclick="event.stopPropagation(); openPayModal({{ $credit->id }}, '{{ $credit->member->first_name }} {{ $credit->member->last_name }}', {{ $credit->balance }})" class="cursor-pointer rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-brand-700">
                                    <i class="fa fa-money"></i> Pay
                                </button>
                                @endif
                                <a href="{{ route('credits.show', $credit->id) }}" onclick="event.stopPropagation()" class="cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50">
                                    View
                                </a>
                            </div>
                        </td>
                    </tr>
                    @if($credit->items_snapshot)
                    <tr id="items-{{ $credit->id }}" style="display: none;">
                        <td colspan="7" class="bg-slate-50 px-5 py-3">
                            <div class="text-xs font-semibold text-slate-500 mb-2">Items Purchased on Credit</div>
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-slate-400">
                                        <th class="pb-1 text-left font-medium">Product</th>
                                        <th class="pb-1 text-right font-medium">Price</th>
                                        <th class="pb-1 text-right font-medium">Qty</th>
                                        <th class="pb-1 text-right font-medium">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach($credit->items_snapshot as $item)
                                    <tr>
                                        <td class="py-1 text-slate-700 font-medium">{{ $item['product_name'] }}</td>
                                        <td class="py-1 text-right text-slate-600">₱{{ number_format($item['price'], 2) }}</td>
                                        <td class="py-1 text-right text-slate-600">{{ $item['quantity'] }}</td>
                                        <td class="py-1 text-right text-slate-700 font-medium">₱{{ number_format($item['subtotal'], 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-16 text-center">
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
function toggleItems(creditId) {
    const row = document.getElementById('items-' + creditId);
    if (row) {
        row.style.display = row.style.display === 'none' ? '' : 'none';
    }
}

function openPayModal(creditId, memberName, balance) {
    document.getElementById('payModalMember').textContent = memberName + ' — Balance: ₱' + balance.toFixed(2);
    document.getElementById('payModalAmount').value = balance.toFixed(2);
    document.getElementById('payModalAmount').max = balance;
    document.getElementById('payModalForm').action = '/credits/' + creditId + '/pay';
    document.getElementById('payModal').style.display = 'flex';
}

function closePayModal() {
    document.getElementById('payModal').style.display = 'none';
}

document.getElementById('payModal').addEventListener('click', function(e) {
    if (e.target === this) closePayModal();
});
</script>
@endsection
