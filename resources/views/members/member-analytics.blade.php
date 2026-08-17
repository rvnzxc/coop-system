@extends('layouts.app')

@section('title', 'Member Analytics')

@section('content')
<style>
    @media print {
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .print-hide {
            display: none !important;
        }
        .transaction-detail {
            display: none !important;
        }
        i.fa-chevron-right {
            display: none !important;
        }
        table {
            page-break-inside: avoid;
        }
        tr {
            page-break-inside: avoid;
        }
    }
</style>

<div class="mx-auto max-w-5xl">
    {{-- Header --}}
    <div class="mb-6 flex flex-col items-start gap-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center">
        <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-brand-600 text-3xl text-white">
            <i class="fa fa-user"></i>
        </div>

        <div class="min-w-0 flex-1">
            <h2 class="truncate text-2xl font-bold text-slate-900">
                {{ $member->first_name }} {{ $member->last_name }}
            </h2>
            <div class="mt-1 text-sm text-slate-500">
                @if($member->email)
                    <div class="mb-1"><i class="fa fa-envelope mr-2"></i>{{ $member->email }}</div>
                @endif
                @if($member->phone)
                    <div class="mb-1"><i class="fa fa-phone mr-2"></i>{{ $member->phone }}</div>
                @endif
                <div class="mt-2">
                    @if($member->is_active)
                        <x-badge color="green"><i class="fa fa-circle text-[6px]"></i> Active</x-badge>
                    @else
                        <x-badge color="red"><i class="fa fa-circle text-[6px]"></i> Inactive</x-badge>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Analytics Dashboard --}}
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Left Column - Summary Stats --}}
        <x-card title="Purchase Summary">
            @php
                $transactionCount = count($transactions);
            @endphp
            <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-lg bg-slate-50 p-4 text-center">
                    <div class="summary-total-purchases text-xl font-bold text-slate-900">₱{{ number_format($member->total_purchases, 2) }}</div>
                    <div class="mt-1 text-xs text-slate-500">Total Purchases</div>
                </div>
                <div class="rounded-lg bg-slate-50 p-4 text-center">
                    <div class="summary-transactions text-xl font-bold text-slate-900">{{ $transactionCount }}</div>
                    <div class="mt-1 text-xs text-slate-500">Transactions</div>
                </div>
                <div class="rounded-lg bg-slate-50 p-4 text-center">
                    <div class="summary-average-purchase text-xl font-bold text-slate-900">₱{{ number_format($transactionCount > 0 ? $member->total_purchases / $transactionCount : 0, 2) }}</div>
                    <div class="mt-1 text-xs text-slate-500">Average Purchase</div>
                </div>
            </div>

            <div class="rounded-lg bg-slate-50 p-5">
                <div class="mb-3 text-sm font-semibold text-slate-900">Member Since</div>
                <div class="text-sm text-slate-500">{{ $member->created_at->format('F d, Y') }}</div>
                <div class="mt-3 text-sm font-semibold text-slate-900">Last Purchase</div>
                <div class="mt-1 text-sm text-slate-500">{{ $member->last_purchase_date ? $member->last_purchase_date->format('F d, Y') : 'Never' }}</div>
            </div>
        </x-card>

        {{-- Right Column - Purchase Analytics --}}
        <x-card title="Purchase Analytics">
            <div class="print-hide mb-4 flex flex-wrap items-center gap-3">
                <label class="text-sm font-medium text-slate-500">Analytics Period:</label>
                <select id="periodFilter" onchange="filterMemberAnalytics()" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    <option value="all">All Time</option>
                    <option value="weekly">Last 7 Days</option>
                    <option value="monthly">Last 30 Days</option>
                    <option value="yearly">Last 365 Days</option>
                </select>
                <x-button color="secondary" size="sm" onclick="window.print()">
                    <i class="fa fa-print"></i> Print Report
                </x-button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                            <th class="px-4 py-3 font-semibold">Date &amp; Time</th>
                            <th class="px-4 py-3 text-right font-semibold">Items</th>
                            <th class="px-4 py-3 text-right font-semibold">Total</th>
                        </tr>
                    </thead>
                    <tbody id="purchaseHistoryTable" class="divide-y divide-slate-100">
                        <!-- Purchase history will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    {{-- Back Button --}}
    <div class="mt-8 text-center">
        <x-button href="{{ route('members.index') }}" color="secondary">
            <i class="fa fa-arrow-left"></i> Back to Members
        </x-button>
    </div>
</div>

<script>
// Get member purchase history, grouped into transactions
const memberTransactions = @json($transactions ?? []);

// Escape helper for server-provided strings rendered into innerHTML
function esc(value) {
    return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// Parse 'YYYY-MM-DD HH:MM:SS' as local time (ISO 8601) for cross-browser safety
function parseDatetime(value) {
    return new Date(String(value).replace(' ', 'T'));
}

function formatDateTime(datetime) {
    if (!datetime) return 'N/A';
    const d = parseDatetime(datetime);
    return d.toLocaleDateString() + ', ' + d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
}

function formatPeso(value) {
    return '₱' + Number(value || 0).toFixed(2);
}

function buildDetailTable(transaction) {
    let rows = '';
    transaction.items.forEach(item => {
        rows += `
            <tr class="border-b border-slate-200 last:border-0">
                <td class="px-4 py-2 text-sm text-slate-700">${esc(item.product_name) || 'N/A'}</td>
                <td class="whitespace-nowrap px-3 py-2 text-right text-sm text-slate-500">${formatPeso(item.price)}</td>
                <td class="whitespace-nowrap px-3 py-2 text-right text-sm text-slate-500">${item.quantity}</td>
                <td class="whitespace-nowrap px-4 py-2 text-right text-sm font-semibold text-slate-700">${formatPeso(item.amount)}</td>
            </tr>`;
    });

    return `
        <div class="rounded-lg border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Items in this transaction</div>
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-100/70 text-xs uppercase tracking-wider text-slate-500">
                        <th class="px-4 py-2 font-semibold">Product</th>
                        <th class="px-3 py-2 text-right font-semibold">Price</th>
                        <th class="px-3 py-2 text-right font-semibold">Qty</th>
                        <th class="px-4 py-2 text-right font-semibold">Line Total</th>
                    </tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>
        </div>`;
}

function buildTransactionRow(transaction, index) {
    const frag = document.createDocumentFragment();

    const row = document.createElement('tr');
    row.className = 'cursor-pointer transition-colors hover:bg-slate-50';

    const dtCell = document.createElement('td');
    dtCell.className = 'px-4 py-3';

    const chevron = document.createElement('i');
    chevron.className = 'fa fa-chevron-right text-[10px] text-slate-400 transition-transform duration-200';
    dtCell.appendChild(chevron);
    dtCell.appendChild(document.createTextNode(' ' + formatDateTime(transaction.datetime)));

    const itemsCell = document.createElement('td');
    itemsCell.className = 'whitespace-nowrap px-4 py-3 text-right';
    itemsCell.textContent = transaction.item_count + (transaction.item_count === 1 ? ' item' : ' items');

    const totalCell = document.createElement('td');
    totalCell.className = 'whitespace-nowrap px-4 py-3 text-right font-semibold';
    totalCell.textContent = formatPeso(transaction.total);

    row.appendChild(dtCell);
    row.appendChild(itemsCell);
    row.appendChild(totalCell);

    const detailRow = document.createElement('tr');
    detailRow.className = 'transaction-detail hidden';
    const detailCell = document.createElement('td');
    detailCell.colSpan = 3;
    detailCell.className = 'bg-slate-50 px-4 py-3';
    detailCell.innerHTML = buildDetailTable(transaction);
    detailRow.appendChild(detailCell);

    row.addEventListener('click', () => {
        detailRow.classList.toggle('hidden');
        chevron.classList.toggle('rotate-90');
    });

    frag.appendChild(row);
    frag.appendChild(detailRow);
    return frag;
}

function filterMemberAnalytics() {
    const period = document.getElementById('periodFilter').value;
    const tableBody = document.getElementById('purchaseHistoryTable');

    // Clear existing rows
    tableBody.innerHTML = '';

    // Filter transactions based on period
    let filteredTransactions = memberTransactions;

    if (period !== 'all') {
        const now = new Date();

        filteredTransactions = memberTransactions.filter(transaction => {
            if (!transaction.datetime) return false;

            const diffTime = now - parseDatetime(transaction.datetime);
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
    filteredTransactions.sort((a, b) => parseDatetime(b.datetime) - parseDatetime(a.datetime));

    // Populate table
    if (filteredTransactions.length === 0) {
        const emptyRow = document.createElement('tr');
        emptyRow.innerHTML = '<td colspan="3" class="px-4 py-10 text-center text-sm text-slate-400">No purchases in this period.</td>';
        tableBody.appendChild(emptyRow);
    } else {
        filteredTransactions.forEach((transaction, index) => {
            tableBody.appendChild(buildTransactionRow(transaction, index));
        });
    }

    // Update summary
    updatePurchaseSummary(filteredTransactions);
}

function updatePurchaseSummary(transactions) {
    const totalPurchases = transactions.reduce((sum, transaction) => sum + Number(transaction.total || 0), 0);
    const transactionCount = transactions.length;
    const averagePurchase = transactionCount > 0 ? totalPurchases / transactionCount : 0;

    // Update summary display
    const totalElement = document.querySelector('.summary-total-purchases');
    if (totalElement) totalElement.textContent = formatPeso(totalPurchases);

    const transactionElement = document.querySelector('.summary-transactions');
    if (transactionElement) transactionElement.textContent = transactionCount;

    const averageElement = document.querySelector('.summary-average-purchase');
    if (averageElement) averageElement.textContent = formatPeso(averagePurchase);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    filterMemberAnalytics();
});
</script>
@endsection
