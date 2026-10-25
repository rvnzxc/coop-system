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
            <x-icon name="arrow-left" class="h-3 w-3" /> Back
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

            <hr class="border-slate-100">

            <div class="space-y-5">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Barcodes</h3>
                    <p class="mt-0.5 text-xs text-slate-500">The internal barcode is generated automatically and assigned when you save this item. Use the Scan button to capture the manufacturer's UPC/EAN so this item can be scanned straight off its packaging.</p>
                </div>

                <x-field label="Internal Barcode" name="barcode">
                    <input type="text" id="barcode" value="{{ $previewBarcode }}" readonly class="{{ $inputClass }} cursor-not-allowed bg-slate-50 text-slate-500">
                    @if($previewBarcode)
                        <div class="mt-3 inline-block rounded-md border border-slate-200 bg-white p-2.5">
                            {!! \App\Services\BarcodeService::generateBarcode($previewBarcode) !!}
                        </div>
                        <p class="mt-1.5 text-xs text-slate-400">This code will be saved with the item — the value is also printed under the bars.</p>
                    @endif
                </x-field>

                <x-field label="Manufacturer Barcode (UPC/EAN)" name="manufacturer_barcode">
                    <div class="flex items-start gap-2">
                        <div class="min-w-0 flex-1">
                            <input type="text" id="manufacturer_barcode" name="manufacturer_barcode" value="{{ old('manufacturer_barcode') }}" placeholder="Scan or type the packaging barcode" autocomplete="off" class="{{ $inputClass }}">
                        </div>
                        <x-button type="button" id="scanButton" color="secondary" class="shrink-0">
                            <i class="fa fa-barcode"></i> Scan
                        </x-button>
                    </div>
                    <p id="barcodeScanStatus" class="mt-1.5 text-xs text-slate-400">Click Scan, then scan the product packaging.</p>
                </x-field>
            </div>

            <div class="flex gap-3 pt-2">
                <x-button type="submit" color="primary" class="flex-1">
                    <x-icon name="plus" class="h-3.5 w-3.5" /> Add Item
                </x-button>
                <x-button type="button" color="secondary" onclick="location.href='{{ route('inventory.index') }}'">
                    Cancel
                </x-button>
            </div>
        </form>
    </x-card>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const field = document.getElementById('manufacturer_barcode');
        const status = document.getElementById('barcodeScanStatus');
        const button = document.getElementById('scanButton');
        if (!field || !status) return;

        const statusBase = 'mt-1.5 text-xs ';

        function setButton(label, icon) {
            if (button) {
                button.innerHTML = '<i class="fa ' + icon + '"></i> ' + label;
            }
        }

        function setStatus(text, tone) {
            status.textContent = text;
            status.className = statusBase + tone;
        }

        function showIdle() {
            if (field.value.trim() === '') {
                setStatus('No barcode yet — click Scan, then scan the product packaging.', 'text-slate-400');
                setButton('Scan', 'fa-barcode');
            } else {
                setStatus('Barcode captured: ' + field.value.trim() + ' — click Add Item to save.', 'font-medium text-emerald-600');
                setButton('Scan again', 'fa-barcode');
            }
        }

        function startScan() {
            setStatus('Scanning… point the scanner at the product barcode.', 'font-medium text-blue-600');
            setButton('Scanning…', 'fa-barcode');
            field.focus();
            field.select();
        }

        if (button) {
            button.addEventListener('click', startScan);
        }

        field.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                field.select();
            }
        });

        field.addEventListener('input', function () {
            if (field.value.trim() !== '') {
                setStatus('Barcode captured: ' + field.value.trim() + ' — click Add Item to save.', 'font-medium text-emerald-600');
                setButton('Scan again', 'fa-barcode');
            } else {
                showIdle();
            }
        });

        showIdle();
    });
</script>
@endsection
