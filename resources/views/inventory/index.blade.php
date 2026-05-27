@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
<div class="inventory-container">
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    
    <div class="inventory-header">
        <h2>Inventory</h2>
        <div class="inventory-actions">
            <form action="{{ route('inventory.index') }}" method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search..." value="{{ $search }}" class="search-input">
                <button type="submit" class="btn-search">Search</button>
            </form>
            <button class="btn-add" onclick="location.href='{{ route('inventory.create') }}'">+ Add Item</button>
        </div>
    </div>

    <div class="inventory-table-container">
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Category</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ ucfirst(strtolower($item->item_name)) }}</td>
                    <td>
                        <span class="quantity {{ $item->quantity <= 10 ? 'low-stock' : '' }}">
                            {{ $item->quantity }}
                        </span>
                    </td>
                    <td>{{ number_format($item->price, 2) }}</td>
                    <td>
                        <span class="category-badge {{ $item->category ?? 'other' }}">
                            {{ ucfirst($item->category ?? 'Other') }}
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-edit" onclick="location.href='{{ route('inventory.edit', $item->id) }}'">
                                <i class="fa fa-edit"></i> Edit
                            </button>
                            <form action="{{ route('inventory.destroy', $item->id) }}" method="POST" class="delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-delete" onclick="return confirm('Are you sure you want to delete this item?')">
                                    <i class="fa fa-trash"></i> Remove
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="no-items">
                        <div class="empty-state">
                            <i class="fa fa-archive"></i>
                            <p>No items found in inventory</p>
                            <button class="btn-add" onclick="location.href='{{ route('inventory.create') }}'">
                                Add Your First Item
                            </button>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection