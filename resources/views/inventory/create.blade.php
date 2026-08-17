@extends('layouts.app')

@section('title', 'Add Item')

@section('content')
@php
    $inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30';
@endphp

<div class="mx-auto max-w-xl">
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Add New Item</h2>
            <p class="mt-0.5 text-sm text-slate-500">Create a new product in your inventory</p>
        </div>
        <x-button href="{{ route('inventory.index') }}" color="secondary" size="sm">
            <i class="fa fa-arrow-left"></i> Back
        </x-button>
    </div>

    <x-card>
        <form action="{{ route('inventory.store') }}" method="POST" class="space-y-5">
            @csrf

            <x-field label="Item Name" name="item_name" required>
                <input type="text" id="item_name" name="item_name" value="{{ old('item_name') }}" required class="{{ $inputClass }}">
            </x-field>

            <x-field label="Quantity" name="quantity" required>
                <input type="number" id="quantity" name="quantity" value="{{ old('quantity') }}" min="0" required class="{{ $inputClass }}">
            </x-field>

            <x-field label="Price" name="price" required>
                <input type="number" id="price" name="price" value="{{ old('price') }}" step="0.01" min="0" required class="{{ $inputClass }}">
            </x-field>

            <x-field label="Category" name="category" required>
                <select id="category" name="category" required class="{{ $inputClass }}">
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
            </x-field>

            <div class="flex gap-3 pt-2">
                <x-button type="submit" color="primary" class="flex-1">
                    <i class="fa fa-plus"></i> Add Item
                </x-button>
                <x-button type="button" color="secondary" onclick="location.href='{{ route('inventory.index') }}'">
                    Cancel
                </x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
