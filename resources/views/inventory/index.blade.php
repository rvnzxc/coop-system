@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
<div class="mx-auto max-w-7xl">
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            <i class="fa fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Page header --}}
    <div class="mb-6 flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
        <div class="min-w-0">
            <h2 class="text-xl font-bold text-slate-900">Inventory</h2>
            <p class="mt-0.5 text-sm text-slate-500">Manage the products available in your store</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <form action="{{ route('inventory.index') }}" method="GET" class="flex gap-2">
                <div class="relative">
                    <i class="fa fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                    <input type="text" name="search" placeholder="Search items..." value="{{ $search }}" class="search-input w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 sm:w-64">
                </div>
                <x-button type="submit" color="secondary" size="sm">Search</x-button>
            </form>
            <x-button href="{{ route('inventory.create') }}" color="primary">
                <i class="fa fa-plus"></i> Add Item
            </x-button>
        </div>
    </div>

    {{-- Summary stats --}}
    @php
        $totalItems = $items->count();
        $lowStockCount = $items->where('quantity', '<=', 10)->count();
        $totalValue = $items->sum(function ($i) { return $i->price * $i->quantity; });
    @endphp
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-base text-brand-600">
                <i class="fa fa-archive"></i>
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">{{ $totalItems }}</div>
                <div class="text-xs text-slate-500">Total Items</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-base text-amber-600">
                <i class="fa fa-exclamation-triangle"></i>
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">{{ $lowStockCount }}</div>
                <div class="text-xs text-slate-500">Low Stock (&le;10)</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-base text-sky-600">
                <i class="fa fa-money"></i>
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">₱{{ number_format($totalValue, 2) }}</div>
                <div class="text-xs text-slate-500">Stock Value</div>
            </div>
        </div>
    </div>

    {{-- Items table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Inventory Items</h3>
            @if($search)
                <a href="{{ route('inventory.index') }}" class="text-xs font-medium text-slate-500 transition-colors hover:text-brand-600">
                    <i class="fa fa-times"></i> Clear search
                </a>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="inventory-table w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5 font-semibold">ID</th>
                        <th class="px-5 py-3.5 font-semibold">Item Name</th>
                        <th class="px-5 py-3.5 font-semibold">Quantity</th>
                        <th class="px-5 py-3.5 font-semibold">Price</th>
                        <th class="px-5 py-3.5 font-semibold">Category</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                    <tr class="transition-colors hover:bg-slate-50">
                        <td class="whitespace-nowrap px-5 py-3.5 text-slate-500">{{ $loop->index + 1 }}</td>
                        <td class="min-w-40 px-5 py-3.5 font-medium text-slate-900">{{ ucfirst(strtolower($item->item_name)) }}</td>
                        <td class="whitespace-nowrap px-5 py-3.5">
                            @if($item->quantity <= 10)
                                <x-badge color="red">{{ $item->quantity }}</x-badge>
                            @else
                                <x-badge color="green">{{ $item->quantity }}</x-badge>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5 font-semibold text-slate-700">₱{{ number_format($item->price, 2) }}</td>
                        <td class="px-5 py-3.5">
                            <x-badge color="gray">{{ ucfirst($item->category ?? 'Other') }}</x-badge>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5">
                            <div class="action-buttons flex items-center justify-end gap-2">
                                <x-button href="{{ route('inventory.edit', $item->id) }}" color="secondary" size="sm">
                                    <i class="fa fa-edit"></i> Edit
                                </x-button>
                                <form action="{{ route('inventory.destroy', $item->id) }}" method="POST" class="delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" color="dangerGhost" size="sm" onclick="return confirm('Are you sure you want to delete this item?')">
                                        <i class="fa fa-trash"></i> Remove
                                    </x-button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="no-items px-5 py-16 text-center">
                            <div class="empty-state flex flex-col items-center">
                                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400">
                                    <i class="fa fa-archive"></i>
                                </div>
                                <p class="text-base font-semibold text-slate-700">{{ $search ? 'No items match your search' : 'No items found in inventory' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $search ? 'Try a different search term.' : 'Start by adding your first item to the store.' }}</p>
                                <x-button href="{{ route('inventory.create') }}" color="primary" class="mt-5">
                                    <i class="fa fa-plus"></i> Add Your First Item
                                </x-button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
