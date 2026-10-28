@extends('layouts.app')

@section('title', 'Transactions')

@section('content')
@php
    $inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30';
    $labelClass = 'mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400';
    $hasFilters = collect($filters)->filter(fn ($value) => ! blank($value))->isNotEmpty();
@endphp

<div class="mx-auto max-w-7xl">
    
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900">Transactions</h2>
        <p class="mt-0.5 text-sm text-slate-500">Review every sale, filter by cashier or date, and export the results</p>
    </div>

    @include('transactions._tabs')

    {{-- Summary cards --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                <x-icon name="shopping-cart" class="h-4 w-4" />
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">{{ number_format($summary['transactions']) }}</div>
                <div class="text-xs text-slate-500">Transactions</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                <x-icon name="money" class="h-4 w-4" />
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">₱{{ number_format($summary['total_sales'], 2) }}</div>
                <div class="text-xs text-slate-500">Total sales</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                <x-icon name="credit-card" class="h-4 w-4" />
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">₱{{ number_format($summary['cash_total'], 2) }}</div>
                <div class="text-xs text-slate-500">Cash total</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                <x-icon name="clock-o" class="h-4 w-4" />
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">₱{{ number_format($summary['credit_total'], 2) }}</div>
                <div class="text-xs text-slate-500">Credit total</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <form id="transactionFilters" method="GET" action="{{ route('transactions.index') }}">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label for="cashierFilter" class="{{ $labelClass }}">Cashier</label>
                    <select id="cashierFilter" name="cashier_id" class="{{ $inputClass }}">
                        <option value="">All cashiers</option>
                        @foreach($cashiers as $cashier)
                            <option value="{{ $cashier->id }}" @selected((string) $filters['cashier_id'] === (string) $cashier->id)>{{ $cashier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="memberFilter" class="{{ $labelClass }}">Member</label>
                    <select id="memberFilter" name="member_id" class="{{ $inputClass }}">
                        <option value="">All members</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}" @selected((string) $filters['member_id'] === (string) $member->id)>{{ $member->full_name }} ({{ $member->member_number }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="paymentFilter" class="{{ $labelClass }}">Payment method</label>
                    <select id="paymentFilter" name="payment_method" class="{{ $inputClass }}">
                        <option value="">All methods</option>
                        <option value="cash" @selected($filters['payment_method'] === 'cash')>Cash</option>
                        <option value="credit" @selected($filters['payment_method'] === 'credit')>Credit</option>
                    </select>
                </div>
                <div>
                    <label for="statusFilter" class="{{ $labelClass }}">Status</label>
                    <select id="statusFilter" name="status" class="{{ $inputClass }}">
                        <option value="">All statuses</option>
                        <option value="completed" @selected($filters['status'] === 'completed')>Completed</option>
                        <option value="voided" @selected($filters['status'] === 'voided')>Voided</option>
                    </select>
                </div>
                <div>
                    <label for="dateFromFilter" class="{{ $labelClass }}">From</label>
                    <input type="date" id="dateFromFilter" name="date_from" value="{{ $filters['date_from'] }}" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="dateToFilter" class="{{ $labelClass }}">To</label>
                    <input type="date" id="dateToFilter" name="date_to" value="{{ $filters['date_to'] }}" class="{{ $inputClass }}">
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Quick range</span>
                    <button type="button" onclick="setRange('today')" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50">Today</button>
                    <button type="button" onclick="setRange('week')" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50">This Week</button>
                    <button type="button" onclick="setRange('month')" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50">This Month</button>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if($hasFilters)
                        <x-button href="{{ route('transactions.index') }}" color="ghost" size="sm">Reset</x-button>
                    @endif
                    <x-button href="{{ route('transactions.export', request()->query()) }}" color="secondary" size="sm">
                        <x-icon name="archive" class="h-3 w-3" /> Export CSV
                    </x-button>
                    <x-button type="submit" color="primary" size="sm">
                        <x-icon name="search" class="h-3 w-3" /> Apply filters
                    </x-button>
                </div>
            </div>
        </form>
    </div>

    {{-- Transactions table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Sales</h3>
            <p class="mt-0.5 text-xs text-slate-500">{{ number_format($sales->total()) }} transaction{{ $sales->total() === 1 ? '' : 's' }} found. Click a row to view its items.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5 font-semibold">Date / Time</th>
                        <th class="px-5 py-3.5 font-semibold">Transaction ID</th>
                        <th class="px-5 py-3.5 font-semibold">Cashier</th>
                        <th class="px-5 py-3.5 font-semibold">Member</th>
                        <th class="px-5 py-3.5 font-semibold">Payment</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Total</th>
                        <th class="px-5 py-3.5 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($sales as $sale)
                        <tr class="cursor-pointer transition-colors hover:bg-slate-50" onclick="openSaleDetail(this)" data-sale="{{ json_encode($sale->toTransactionPayload()) }}">
                            <td class="whitespace-nowrap px-5 py-3.5 text-slate-600">{{ $sale->created_at?->format('M d, Y g:i A') }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <div class="font-semibold text-slate-900">#{{ $sale->id }}</div>
                                <div class="text-xs text-slate-400">{{ $sale->items->count() }} item{{ $sale->items->count() === 1 ? '' : 's' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-slate-600">{{ $sale->cashier_name }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-slate-600">{{ $sale->member_name }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                @if($sale->payment_method === 'credit')
                                    <x-badge color="amber">Credit</x-badge>
                                @else
                                    <x-badge color="blue">Cash</x-badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right font-semibold text-slate-900">₱{{ number_format((float) $sale->total, 2) }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                @if($sale->status === 'voided')
                                    <x-badge color="red">Voided</x-badge>
                                @else
                                    <x-badge color="green">Completed</x-badge>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <x-icon name="money" class="h-6 w-6" />
                                    </div>
                                    <p class="text-base font-semibold text-slate-700">No transactions found</p>
                                    <p class="mt-1 text-sm text-slate-500">Try a different date range or clear the filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sales->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $sales->links() }}
            </div>
        @endif
    </div>

    {{-- Per-cashier summary --}}
    <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Per-cashier summary</h3>
            <p class="mt-0.5 text-xs text-slate-500">
                Completed sales
                @if($filters['date_from'] || $filters['date_to'])
                    from {{ $filters['date_from'] ? \Illuminate\Support\Carbon::parse($filters['date_from'])->format('M d, Y') : 'the beginning' }}
                    to {{ $filters['date_to'] ? \Illuminate\Support\Carbon::parse($filters['date_to'])->format('M d, Y') : 'today' }}
                @else
                    for all time
                @endif
            </p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5 font-semibold">Cashier</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Transactions</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Total Sales</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($perCashier as $row)
                        <tr class="transition-colors hover:bg-slate-50">
                            <td class="px-5 py-3.5 font-medium text-slate-900">{{ $row['cashier'] }}</td>
                            <td class="px-5 py-3.5 text-right text-slate-600">{{ number_format($row['transactions']) }}</td>
                            <td class="px-5 py-3.5 text-right font-semibold text-slate-900">₱{{ number_format($row['total_sales'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center text-sm text-slate-500">No completed sales in this date range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Sale detail modal --}}
<div id="saleDetailModal" class="fixed inset-0 z-50 items-center justify-center bg-slate-900/50 p-4" style="display: none;" onclick="if (event.target === this) closeSaleDetail()">
    <div class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white p-6 shadow-xl">
        <button type="button" onclick="closeSaleDetail()" class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600" aria-label="Close">&times;</button>

        <div class="flex flex-wrap items-start justify-between gap-3 pr-8">
            <div>
                <h3 id="detailTransaction" class="text-base font-bold text-slate-900"></h3>
                <p id="detailDate" class="mt-0.5 text-sm text-slate-500"></p>
            </div>
            <span id="detailStatus"></span>
        </div>

        <dl class="mt-5 grid grid-cols-1 gap-3 rounded-lg bg-slate-50 p-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-slate-400">Cashier</dt>
                <dd id="detailCashier" class="mt-0.5 font-medium text-slate-900"></dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-slate-400">Member</dt>
                <dd id="detailMember" class="mt-0.5 font-medium text-slate-900"></dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-slate-400">Payment method</dt>
                <dd id="detailPayment" class="mt-0.5 font-medium text-slate-900"></dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total</dt>
                <dd id="detailTotal" class="mt-0.5 font-semibold text-slate-900"></dd>
            </div>
            <div id="detailAmountReceivedWrap">
                <dt class="text-xs font-semibold uppercase tracking-wider text-slate-400">Amount Received</dt>
                <dd id="detailAmountReceived" class="mt-0.5 font-medium text-slate-900"></dd>
            </div>
            <div id="detailChangeWrap">
                <dt class="text-xs font-semibold uppercase tracking-wider text-slate-400">Change</dt>
                <dd id="detailChange" class="mt-0.5 font-semibold text-emerald-700"></dd>
            </div>
        </dl>

        <div class="mt-5 overflow-hidden rounded-lg border border-slate-200">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-2.5 font-semibold">Item</th>
                        <th class="px-4 py-2.5 text-center font-semibold">Qty</th>
                        <th class="px-4 py-2.5 text-right font-semibold">Unit Price</th>
                        <th class="px-4 py-2.5 text-right font-semibold">Subtotal</th>
                    </tr>
                </thead>
                <tbody id="detailItems" class="divide-y divide-slate-100"></tbody>
            </table>
        </div>

        <div id="detailVoidInfo" class="mt-4 hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

        <div class="mt-6 flex justify-end">
            <x-button type="button" color="secondary" onclick="closeSaleDetail()">Close</x-button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        @if($errors->any())
            showToast(@json($errors->first()), 'error');
        @endif
    });

    function setRange(range) {
        const from = document.getElementById('dateFromFilter');
        const to = document.getElementById('dateToFilter');
        const today = new Date();
        let start = new Date(today);
        let end = new Date(today);

        if (range === 'week') {
            const day = (today.getDay() + 6) % 7;
            start.setDate(today.getDate() - day);
        } else if (range === 'month') {
            start = new Date(today.getFullYear(), today.getMonth(), 1);
        }

        from.value = formatDate(start);
        to.value = formatDate(end);
        document.getElementById('transactionFilters').submit();
    }

    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function money(value) {
        return '₱' + Number(value || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function openSaleDetail(row) {
        let sale;

        try {
            sale = JSON.parse(row.dataset.sale);
        } catch (error) {
            return;
        }

        document.getElementById('detailTransaction').textContent = 'Transaction #' + sale.id;
        document.getElementById('detailDate').textContent = sale.created_at || '';
        document.getElementById('detailCashier').textContent = sale.cashier || 'Unknown';
        document.getElementById('detailMember').textContent = sale.member_number
            ? sale.member + ' (' + sale.member_number + ')'
            : sale.member;
        document.getElementById('detailPayment').textContent = sale.payment_method === 'credit' ? 'Credit' : 'Cash';
        document.getElementById('detailTotal').textContent = money(sale.total);

        const receivedWrap = document.getElementById('detailAmountReceivedWrap');
        const receivedEl = document.getElementById('detailAmountReceived');
        const changeWrap = document.getElementById('detailChangeWrap');
        const changeEl = document.getElementById('detailChange');
        if (sale.payment_method === 'cash' && sale.amount_received !== null && sale.amount_received !== undefined) {
            receivedWrap.style.display = '';
            changeWrap.style.display = '';
            receivedEl.textContent = money(sale.amount_received);
            changeEl.textContent = money(sale.change_amount);
        } else {
            receivedWrap.style.display = 'none';
            changeWrap.style.display = 'none';
        }

        setDetailStatus(sale);

        const itemsBody = document.getElementById('detailItems');
        itemsBody.innerHTML = '';

        (sale.items || []).forEach(function (item) {
            const row = document.createElement('tr');

            const name = document.createElement('td');
            name.className = 'px-4 py-2.5 text-slate-800';
            name.textContent = item.product_name;

            const quantity = document.createElement('td');
            quantity.className = 'px-4 py-2.5 text-center text-slate-600';
            quantity.textContent = item.quantity;

            const unitPrice = document.createElement('td');
            unitPrice.className = 'whitespace-nowrap px-4 py-2.5 text-right text-slate-600';
            unitPrice.textContent = money(item.unit_price);

            const subtotal = document.createElement('td');
            subtotal.className = 'whitespace-nowrap px-4 py-2.5 text-right font-semibold text-slate-900';
            subtotal.textContent = money(item.subtotal);

            row.append(name, quantity, unitPrice, subtotal);
            itemsBody.appendChild(row);
        });

        const voidInfo = document.getElementById('detailVoidInfo');
        if (sale.status === 'voided') {
            voidInfo.classList.remove('hidden');
            voidInfo.textContent = 'Voided' + (sale.voided_by ? ' by ' + sale.voided_by : '') + (sale.void_reason ? ': ' + sale.void_reason : '');
        } else {
            voidInfo.classList.add('hidden');
            voidInfo.textContent = '';
        }

        const modal = document.getElementById('saleDetailModal');
        modal.style.display = 'flex';
    }

    function setDetailStatus(sale) {
        const container = document.getElementById('detailStatus');
        const badge = document.createElement('span');
        const voided = sale.status === 'voided';

        badge.className = 'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ' +
            (voided ? 'bg-red-50 text-red-700 ring-red-600/20' : 'bg-emerald-50 text-emerald-700 ring-emerald-600/20');
        badge.textContent = voided ? 'Voided' : 'Completed';

        container.innerHTML = '';
        container.appendChild(badge);
    }

    function closeSaleDetail() {
        document.getElementById('saleDetailModal').style.display = 'none';
    }

</script>
@endsection
