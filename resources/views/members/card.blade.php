@extends('layouts.app')

@section('title', 'Member Card')

@section('content')
<style>
    .member-card-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background: #f5f5f5;
        padding: 20px;
    }
    
    .member-card {
        width: 350px;
        height: 220px;
        background: linear-gradient(135deg, #1b3a1b 0%, #2d5a2d 100%);
        border-radius: 15px;
        padding: 20px;
        color: white;
        position: relative;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    
    .card-header {
        text-align: center;
        border-bottom: 2px solid rgba(255,255,255,0.3);
        padding-bottom: 10px;
        margin-bottom: 15px;
    }
    
    .card-header h2 {
        margin: 0;
        font-size: 18px;
        font-weight: bold;
        color: #d0ff00;
    }
    
    .card-body {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex: 1;
    }
    
    .member-info {
        flex: 1;
    }
    
    .member-name {
        font-size: 16px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .member-id {
        font-size: 14px;
        color: #d0ff00;
        font-weight: bold;
    }
    
    .barcode-container {
        text-align: center;
        margin-top: 15px;
    }
    
    .barcode {
        background: white;
        padding: 10px;
        border-radius: 5px;
        display: inline-block;
    }
    
    .card-footer {
        text-align: center;
        font-size: 12px;
        opacity: 0.8;
        margin-top: 10px;
    }
    
    .print-button {
        position: fixed;
        top: 20px;
        right: 20px;
        background: #1b3a1b;
        color: #d0ff00;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        font-size: 14px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
    }
    
    .print-button:hover {
        background: #2d5a2d;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.3);
    }
    
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

<div class="member-card-container">
    <button class="print-button" onclick="window.print()">
        <i class="fa fa-print"></i> Print Card
    </button>
    
    <div class="member-card">
        <div class="card-header">
            <h2>MEMBERSHIP CARD</h2>
        </div>
        
        <div class="card-body">
            <div class="member-info">
                <div class="member-name">{{ $member->first_name }} {{ $member->last_name }}</div>
                <div class="member-id">{{ $member->member_number }}</div>
            </div>
        </div>
        
        <div class="barcode-container">
            <div class="barcode">
                {!! \App\Services\BarcodeService::generateBarcode($member->member_number) !!}
            </div>
        </div>
        
            </div>
</div>
@endsection
