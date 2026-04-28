@extends('layouts.app')

@section('title', 'Edit Item')

@section('content')
<div class="inventory-container">
    <div class="inventory-header">
        <h2>Edit Item</h2>
        <div class="inventory-actions">
            <button class="btn-add" onclick="location.href='{{ route('inventory.index') }}'">Back to Inventory</button>
        </div>
    </div>

    <div class="inventory-form-container">
        <form action="{{ route('inventory.update', $item->id) }}" method="POST" class="inventory-form">
            @csrf
            @method('PUT')
            
            <div class="form-group">
                <label for="item_name">Item Name</label>
                <input type="text" id="item_name" name="item_name" class="form-control" value="{{ $item->item_name }}" required>
                @error('item_name')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="quantity">Quantity</label>
                <input type="number" id="quantity" name="quantity" class="form-control" value="{{ $item->quantity }}" min="0" required>
                @error('quantity')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" id="price" name="price" class="form-control" value="{{ $item->price }}" step="0.01" min="0" required>
                @error('price')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" class="form-control" required>
                    <option value="">Select Category</option>
                    <option value="disposable" {{ $item->category == 'disposable' ? 'selected' : '' }}>Disposable</option>
                    <option value="condiments" {{ $item->category == 'condiments' ? 'selected' : '' }}>Condiments</option>
                    <option value="frozen" {{ $item->category == 'frozen' ? 'selected' : '' }}>Frozen</option>
                    <option value="canned" {{ $item->category == 'canned' ? 'selected' : '' }}>Canned</option>
                    <option value="laundry" {{ $item->category == 'laundry' ? 'selected' : '' }}>Laundry</option>
                    <option value="personal-care" {{ $item->category == 'personal-care' ? 'selected' : '' }}>Personal Care</option>
                    <option value="snacks" {{ $item->category == 'snacks' ? 'selected' : '' }}>Snacks</option>
                    <option value="ice-cream" {{ $item->category == 'ice-cream' ? 'selected' : '' }}>Ice Cream</option>
                    <option value="biscuits" {{ $item->category == 'biscuits' ? 'selected' : '' }}>Biscuits</option>
                    <option value="beverages" {{ $item->category == 'beverages' ? 'selected' : '' }}>Beverages</option>
                    <option value="candy" {{ $item->category == 'candy' ? 'selected' : '' }}>Candy</option>
                    <option value="essentials" {{ $item->category == 'essentials' ? 'selected' : '' }}>Essentials</option>
                </select>
                @error('category')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Update Item</button>
                <button type="button" class="btn-cancel" onclick="location.href='{{ route('inventory.index') }}'">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection
