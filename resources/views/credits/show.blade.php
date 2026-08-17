@extends('layouts.app')

@section('title', 'Credit Details')

@section('content')
<div class="mx-auto max-w-4xl">
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            <i class="fa fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('credits.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm text-slate-600 transition-colors hover:bg-slate-50">
            <i class="fa fa-arrow-left"></i>
        </a>
        <div>
            <h2 class="text-xl font-bold text-slate-900">Credit #{{ $credit->id }}</h2>
            <p class="mt-0.5 text-sm text-slate-500">Created {{ $credit->created_at->format('M d, Y g:i A') }}</p>
        </div>
    </div>

    {{-- Credit summary --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs text-slate-500">Member</div>
            <div class="mt-1 font-semibold text-slate-900">{{ $credit->member->first_name }} {{ $credit->member->last_name }}</div>
            <div class="text-xs text-slate-400">{{ $credit->member->member_number }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs text-slate-500">Credit Amount</div>
            <div class="mt-1 text-xl font-bold text-slate-900">₱{{ number_format($credit->amount, 2) }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs text-slate-500">Amount Paid</div>
            <div class="mt-1 text-xl font-bold text-emerald-600">₱{{ number_format($credit->amount_paid, 2) }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs text-slate-500">Remaining Balance</div>
            <div class="mt-1 text-xl font-bold {{ $credit->balance > 0 ? 'text-red-600' : 'text-emerald-600' }}">₱{{ number_format($credit->balance, 2) }}</div>
            <div class="mt-1">
                @if($credit->status === 'paid')
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700"><i class="fa fa-check-circle"></i> Paid</span>
                @elseif($credit->status === 'partial')
                    <span class="inline-flex items-center gap-1 rounded-full bg-yellow-50 px-2.5 py-0.5 text-xs font-medium text-yellow-700"><i class="fa fa-clock-o"></i> Partial</span>
                @else
                    <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700"><i class="fa fa-exclamation-circle"></i> Unpaid</span>
                @endif
            </div>
        </div>
    </div>

    @if($credit->notes)
    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="text-xs font-semibold text-slate-500">Notes</div>
        <div class="mt-1 text-sm text-slate-700">{{ $credit->notes }}</div>
    </div>
    @endif

    {{-- Items purchased --}}
    @if($credit->items_snapshot)
    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold text-slate-900">Items Purchased on Credit</h3>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-xs uppercase tracking-wider text-slate-500">
                    <th class="pb-2 text-left font-semibold">Product</th>
                    <th class="pb-2 text-right font-semibold">Price</th>
                    <th class="pb-2 text-right font-semibold">Qty</th>
                    <th class="pb-2 text-right font-semibold">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($credit->items_snapshot as $item)
                <tr>
                    <td class="py-2.5 font-medium text-slate-900">{{ $item['product_name'] }}</td>
                    <td class="py-2.5 text-right text-slate-600">₱{{ number_format($item['price'], 2) }}</td>
                    <td class="py-2.5 text-right text-slate-600">{{ $item['quantity'] }}</td>
                    <td class="py-2.5 text-right font-semibold text-slate-900">₱{{ number_format($item['subtotal'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t border-slate-200">
                    <td colspan="3" class="pt-2.5 text-right text-sm font-semibold text-slate-700">Total</td>
                    <td class="pt-2.5 text-right text-base font-bold text-slate-900">₱{{ number_format($credit->amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif

    {{-- Payment history --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Payment History</h3>
            @if($credit->status !== 'paid')
            <a href="{{ route('credits.index') }}" class="text-xs font-medium text-brand-600 hover:text-brand-700">Back to list</a>
            @endif
        </div>
        <div class="max-h-96 overflow-auto">
            <table class="w-full text-left text-sm">
                <thead class="sticky top-0 z-10 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5 font-semibold">#</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Amount Paid</th>
                        <th class="px-5 py-3.5 font-semibold">Paid At</th>
                        <th class="px-5 py-3.5 font-semibold">Received By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($credit->payments as $index => $payment)
                    <tr class="transition-colors hover:bg-slate-50">
                        <td class="px-5 py-3.5 text-slate-500">{{ $index + 1 }}</td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-right font-semibold text-emerald-600">₱{{ number_format($payment->amount_paid, 2) }}</td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-slate-500">{{ $payment->paid_at->format('M d, Y g:i A') }}</td>
                        <td class="px-5 py-3.5 text-slate-600">{{ $payment->receiver->name ?? 'System' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-5 py-12 text-center text-sm text-slate-400">No payments recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
