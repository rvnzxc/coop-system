@extends('layouts.app')

@section('title', 'Edit Item')

@section('content')
@php
    $inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30';
@endphp

<div class="mx-auto max-w-xl">
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Edit Item</h2>
            <p class="mt-0.5 text-sm text-slate-500">Update the details of this product</p>
        </div>
        <x-button href="{{ route('inventory.index') }}" color="secondary" size="sm">
            <i class="fa fa-arrow-left"></i> Back
        </x-button>
    </div>

    <x-card>
        <form action="{{ route('inventory.update', $item->id) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <x-field label="Item Name" name="item_name" required>
                <input type="text" id="item_name" name="item_name" value="{{ $item->item_name }}" required class="{{ $inputClass }}">
            </x-field>

            <x-field label="Quantity" name="quantity" required>
                <input type="number" id="quantity" name="quantity" value="{{ $item->quantity }}" min="0" required class="{{ $inputClass }}">
            </x-field>

            <x-field label="Price" name="price" required>
                <input type="number" id="price" name="price" value="{{ $item->price }}" step="0.01" min="0" required class="{{ $inputClass }}">
            </x-field>

            <x-field label="Category" name="category" required>
                <select id="category" name="category" required class="{{ $inputClass }}">
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
            </x-field>

            <div class="flex gap-3 pt-2">
                <x-button type="submit" color="primary" class="flex-1">
                    <i class="fa fa-save"></i> Update Item
                </x-button>
                <x-button type="button" color="secondary" onclick="location.href='{{ route('inventory.index') }}'">
                    Cancel
                </x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
