@extends('layouts.app')

@section('title', 'Credit Details')

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('credits.index') }}" class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm text-slate-600 transition-colors hover:bg-slate-50">
            <x-icon name="arrow-left" class="h-4 w-4" />
        </a>
        <div>
            <h2 class="text-xl font-bold text-slate-900">Credit #{{ $credit->id }}</h2>
            <p class="mt-0.5 text-sm text-slate-500">Created {{ $credit->created_at->format('M d, Y g:i A') }}</p>
        </div>
    </div>

    {{-- Credit summary --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs text-slate-500">Member</div>
            <div class="mt-1 font-semibold text-slate-900">{{ $credit->member->first_name }} {{ $credit->member->last_name }}</div>
            <div class="text-xs text-slate-400">{{ $credit->member->member_number }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs text-slate-500">Credit Amount</div>
            <div class="mt-1 text-xl font-bold text-slate-900">&#8369;{{ number_format($credit->amount, 2) }}</div>
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
                    <td class="py-2.5 text-right text-slate-600">&#8369;{{ number_format($item['price'], 2) }}</td>
                    <td class="py-2.5 text-right text-slate-600">{{ $item['quantity'] }}</td>
                    <td class="py-2.5 text-right font-semibold text-slate-900">&#8369;{{ number_format($item['subtotal'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t border-slate-200">
                    <td colspan="3" class="pt-2.5 text-right text-sm font-semibold text-slate-700">Total</td>
                    <td class="pt-2.5 text-right text-base font-bold text-slate-900">&#8369;{{ number_format($credit->amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>
@endsection
