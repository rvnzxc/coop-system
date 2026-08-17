@extends('layouts.app')

@section('title', 'Member Card')

@section('content')
<style>
    @media print {
        body * {
            visibility: hidden;
        }
        .member-card-container, .member-card-container * {
            visibility: visible;
        }
        .member-card-container {
            position: absolute;
            left: 0;
            top: 0;
            background: white;
        }
        .print-button {
            display: none;
        }
        .member-card {
            box-shadow: none;
        }
    }
</style>

<div class="member-card-container flex min-h-[calc(100vh-8rem)] flex-col items-center justify-center gap-6 p-6">
    <button class="print-button flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-brand-700" onclick="window.print()">
        <i class="fa fa-print"></i> Print Card
    </button>

    <div class="member-card flex w-full max-w-[350px] flex-col justify-between rounded-2xl bg-gradient-to-br from-brand-800 to-brand-600 p-5 text-white shadow-xl">
        <div class="card-header border-b-2 border-white/30 pb-2.5 text-center">
            <h2 class="text-lg font-bold text-white">MEMBERSHIP CARD</h2>
        </div>

        <div class="card-body flex flex-1 items-center">
            <div class="member-info flex-1">
                <div class="member-name text-base font-bold">{{ $member->first_name }} {{ $member->last_name }}</div>
                <div class="member-id text-sm font-bold text-brand-100">{{ $member->member_number }}</div>
            </div>
        </div>

        <div class="barcode-container mt-3 text-center">
            <div class="barcode inline-block rounded-md bg-white p-2.5">
                {!! \App\Services\BarcodeService::generateBarcode($member->member_number) !!}
            </div>
            <div class="card-footer mt-2 text-xs opacity-80">Cavite College of Fisheries Multi-Purpose Cooperative</div>
        </div>
    </div>
</div>
@endsection
