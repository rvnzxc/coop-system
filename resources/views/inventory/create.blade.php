@extends('layouts.app')

@section('title', 'Add Item')

@section('content')
<div class="inventory-container">
    <div class="inventory-header">
        <h2>Add New Item</h2>
        <div class="inventory-actions">
            <button class="btn-add" onclick="location.href='{{ route('inventory.index') }}'">Back to Inventory</button>
        </div>
    </div>

    <div class="inventory-form-container">
        <form action="{{ route('inventory.store') }}" method="POST" class="inventory-form">
            @csrf
            
            <div class="form-group">
                <label for="item_name">Item Name</label>
                <input type="text" id="item_name" name="item_name" class="form-control" required>
                @error('item_name')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="quantity">Quantity</label>
                <input type="number" id="quantity" name="quantity" class="form-control" min="0" required>
                @error('quantity')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required>
                @error('price')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" class="form-control" required>
                    <option value="">Select Category</option>
                    <option value="disposable">Disposable</option>
                    <option value="condiments">Condiments</option>
                    <option value="frozen">Frozen</option>
                    <option value="canned">Canned</option>
                    <option value="laundry">Laundry</option>
                    <option value="personal-care">Personal Care</option>
                    <option value="snacks">Snacks</option>
                    <option value="ice-cream">Ice Cream</option>
                    <option value="biscuits">Biscuits</option>
                    <option value="beverages">Beverages</option>
                    <option value="candy">Candy</option>
                    <option value="essentials">Essentials</option>
                </select>
                @error('category')
                    <span class="error-message">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit">Add Item</button>
                <button type="button" class="btn-cancel" onclick="location.href='{{ route('inventory.index') }}'">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection
