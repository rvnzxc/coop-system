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

            <hr class="border-slate-100">

            <div class="space-y-5">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Barcodes</h3>
                    <p class="mt-0.5 text-xs text-slate-500">The internal barcode is generated automatically. Use the Scan button to capture the manufacturer's UPC/EAN so this item can be scanned straight off its packaging.</p>
                </div>

                <x-field label="Internal Barcode" name="barcode">
                    <input type="text" id="barcode" value="{{ $item->barcode }}" readonly class="{{ $inputClass }} cursor-not-allowed bg-slate-50 text-slate-500">
                    @if($item->barcode)
                        <div class="mt-3 inline-block rounded-md border border-slate-200 bg-white p-2.5">
                            {!! \App\Services\BarcodeService::generateBarcode($item->barcode) !!}
                        </div>
                        <p class="mt-1.5 text-xs text-slate-400">Scan this code instead of typing it — the value is also printed under the bars.</p>
                    @endif
                </x-field>

                <x-field label="Manufacturer Barcode (UPC/EAN)" name="manufacturer_barcode">
                    <div class="flex items-start gap-2">
                        <div class="min-w-0 flex-1">
                            <input type="text" id="manufacturer_barcode" name="manufacturer_barcode" value="{{ old('manufacturer_barcode', $item->manufacturer_barcode) }}" placeholder="Scan or type the packaging barcode" autocomplete="off" class="{{ $inputClass }}">
                        </div>
                        <x-button type="button" id="scanButton" color="secondary" class="shrink-0">
                            <i class="fa fa-barcode"></i> Scan
                        </x-button>
                    </div>
                    <p id="barcodeScanStatus" class="mt-1.5 text-xs text-slate-400">Click Scan, then scan the product packaging.</p>
                    @if($item->manufacturer_barcode)
                        <div class="mt-3 inline-block rounded-md border border-slate-200 bg-white p-2.5">
                            {!! \App\Services\BarcodeService::generateBarcode($item->manufacturer_barcode) !!}
                        </div>
                        <p class="mt-1.5 text-xs text-slate-400">Scan this code straight off the packaging — the value is also printed under the bars.</p>
                    @endif
                </x-field>
            </div>

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
            const value = field.value.trim();

            if (value === '') {
                setStatus('No barcode yet — click Scan, then scan the product packaging.', 'text-slate-400');
                setButton('Scan', 'fa-barcode');
            } else {
                setStatus('Barcode on file: ' + value + '. Click Scan to replace it.', 'text-slate-400');
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
                setStatus('Barcode captured: ' + field.value.trim() + ' — click Update to save.', 'font-medium text-emerald-600');
                setButton('Scan again', 'fa-barcode');
            } else {
                showIdle();
            }
        });

        showIdle();
    });
</script>
@endsection
