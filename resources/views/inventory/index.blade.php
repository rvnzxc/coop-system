@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
@php
    $canManageInventory = auth()->user()->isAdmin();
@endphp
<div class="mx-auto max-w-7xl">
    {{-- Page header --}}
    <div class="mb-6 flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <h2 class="text-xl font-bold text-slate-900">Inventory</h2>
                @unless($canManageInventory)
                    <x-badge color="blue">View only</x-badge>
                @endunless
            </div>
            <p class="mt-0.5 text-sm text-slate-500">
                {{ $canManageInventory ? 'Manage the products available in your store' : 'View the products available in your store' }}
            </p>
        </div>
        <div class="table-toolbar flex w-full flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center md:w-auto md:flex-nowrap">
            <form action="{{ route('inventory.index') }}" method="GET" class="flex w-full min-w-0 gap-2 sm:w-auto">
                @if($sort)
                    <input type="hidden" name="sort" value="{{ $sort }}">
                @endif
                <div class="relative min-w-0 flex-1 sm:flex-none">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                    <input type="text" name="search" placeholder="Search items..." value="{{ $search }}" class="search-input table-search-input w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 sm:w-56 lg:w-64">
                </div>
                <x-button type="submit" color="secondary" size="sm" class="table-toolbar-btn">Search</x-button>
            </form>
            @if($canManageInventory)
                <x-button href="{{ route('inventory.create') }}" color="primary" class="table-toolbar-btn w-full justify-center sm:w-auto">
                    <x-icon name="plus" class="h-3.5 w-3.5" /> Add Item
                </x-button>
            @endif
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
<x-icon name="archive" class="h-6 w-6" />
                </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">{{ $totalItems }}</div>
                <div class="text-xs text-slate-500">Total Items</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-base text-amber-600">
                <x-icon name="exclamation-triangle" class="h-4 w-4" />
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">{{ $lowStockCount }}</div>
                <div class="text-xs text-slate-500">Low Stock (&le;10)</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-base text-sky-600">
                <x-icon name="money" class="h-4 w-4" />
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">&#8369;{{ number_format($totalValue, 2) }}</div>
                <div class="text-xs text-slate-500">Stock Value</div>
            </div>
        </div>
    </div>

    {{-- Items table --}}
    <div class="min-w-0 max-w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="text-sm font-semibold text-slate-900">Inventory Items</h3>
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Sort quantity</span>
                <x-button href="{{ route('inventory.index', array_filter(['search' => $search, 'sort' => $sort === 'high' ? null : 'high'], fn ($value) => $value !== null && $value !== '')) }}" color="{{ $sort === 'high' ? 'primary' : 'secondary' }}" size="sm" class="table-toolbar-btn">
                    <i class="fa fa-sort-amount-desc"></i> Highest
                </x-button>
                <x-button href="{{ route('inventory.index', array_filter(['search' => $search, 'sort' => $sort === 'low' ? null : 'low'], fn ($value) => $value !== null && $value !== '')) }}" color="{{ $sort === 'low' ? 'primary' : 'secondary' }}" size="sm" class="table-toolbar-btn">
                    <i class="fa fa-sort-amount-asc"></i> Lowest
                </x-button>
                @if($search || $sort)
                    <a href="{{ route('inventory.index') }}" class="inline-flex items-center gap-1 text-xs font-medium text-slate-500 transition-colors hover:text-brand-600">
                        <x-icon name="times" class="h-3 w-3" /> Clear filters
                    </a>
                @endif
            </div>
        </div>
        <div class="app-table-frame min-w-0 max-w-full" data-table-frame>
            <div class="app-table-wrap" data-table-scroll>
            <table class="app-table inventory-table">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="tablet-hide px-3 py-3.5 xl:px-4 2xl:px-5 font-semibold">ID</th>
                        <th class="col-sticky-left px-3 py-3.5 xl:px-4 2xl:px-5 font-semibold">Item Name</th>
                        <th class="px-3 py-3.5 xl:px-4 2xl:px-5 font-semibold">Quantity</th>
                        <th class="px-3 py-3.5 xl:px-4 2xl:px-5 font-semibold">Price</th>
                        <th class="px-3 py-3.5 xl:px-4 2xl:px-5 font-semibold">Category</th>
                        <th class="tablet-hide px-3 py-3.5 xl:px-4 2xl:px-5 font-semibold">Internal Barcode</th>
                        <th class="col-sticky-right px-3 py-3.5 xl:px-4 2xl:px-5 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                    <tr class="transition-colors hover:bg-slate-50">
                        <td class="tablet-hide whitespace-nowrap px-3 py-3.5 xl:px-4 2xl:px-5 text-slate-500">{{ $loop->index + 1 }}</td>
                        <td class="col-sticky-left cell-wrap px-3 py-3.5 xl:px-4 2xl:px-5 font-medium text-slate-900">{{ ucfirst(strtolower($item->item_name)) }}</td>
                        <td class="whitespace-nowrap px-3 py-3.5 xl:px-4 2xl:px-5">
                            @if($item->quantity <= 10)
                                <x-badge color="red">{{ $item->quantity }}</x-badge>
                            @else
                                <x-badge color="green">{{ $item->quantity }}</x-badge>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-3.5 xl:px-4 2xl:px-5 font-semibold text-slate-700">&#8369;{{ number_format($item->price, 2) }}</td>
                        <td class="px-3 py-3.5 xl:px-4 2xl:px-5">
                            <x-badge color="gray">{{ ucfirst($item->category ?? 'Other') }}</x-badge>
                        </td>
                        <td class="tablet-hide whitespace-nowrap px-3 py-3.5 xl:px-4 2xl:px-5">
                            @if($item->barcode)
                                <div class="flex min-w-0 items-center gap-2">
                                    <span class="min-w-0 max-w-[9rem] truncate font-mono text-xs text-slate-500" title="{{ $item->barcode }}">{{ $item->barcode }}</span>
                                    <x-button href="{{ route('inventory.barcode.download', $item->barcode) }}" color="secondary" size="sm" title="Download internal barcode" aria-label="Download internal barcode for {{ $item->item_name }}" data-download-png>
                                        <x-download-icon class="h-3.5 w-3.5" />
                                    </x-button>
                                </div>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="col-sticky-right whitespace-nowrap px-3 py-3.5 xl:px-4 2xl:px-5">
                            <div class="table-actions">
                                @if($item->barcode)
                                    <x-button href="{{ route('inventory.barcode.download', $item->barcode) }}" color="secondary" size="sm" class="tablet-only !h-8 !w-8 !px-0" title="Download internal barcode" aria-label="Download internal barcode for {{ $item->item_name }}" data-download-png>
                                        <x-download-icon class="h-3.5 w-3.5" />
                                    </x-button>
                                @endif
                                @if($canManageInventory)
                                    <x-button href="{{ route('inventory.edit', $item->id) }}" color="secondary" size="sm" class="!h-8 !w-8 !px-0" title="Edit Item" aria-label="Edit {{ $item->item_name }}">
                                        <x-icon name="edit" class="h-3 w-3" />
                                    </x-button>
                                    <form action="{{ route('inventory.destroy', $item->id) }}" method="POST" class="delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" color="dangerGhost" size="sm" class="!h-8 !w-8 !px-0" title="Remove Item" aria-label="Remove {{ $item->item_name }}" onclick="return confirmDestructive(event, 'Are you sure you want to delete this item?', 'Delete Item')">
                                            <x-icon name="trash" class="h-3 w-3" />
                                        </x-button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="no-items cell-empty px-5 py-16 text-center">
                            <div class="empty-state flex flex-col items-center">
                                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400">
<x-icon name="archive" class="h-4 w-4" />
                                </div>
                                <p class="text-base font-semibold text-slate-700">{{ ($search || $sort) ? 'No items match your filters' : 'No items found in inventory' }}</p>
                                <p class="mt-1 text-sm text-slate-500">
                                    @if($search || $sort)
                                        Try a different search term or clear the filters.
                                    @elseif($canManageInventory)
                                        Start by adding your first item to the store.
                                    @else
                                        There are no products to display right now.
                                    @endif
                                </p>
                                @if($canManageInventory)
                                    <x-button href="{{ route('inventory.create') }}" color="primary" class="mt-5">
                                        <x-icon name="plus" class="h-3.5 w-3.5" /> Add Your First Item
                                    </x-button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
@endsection
