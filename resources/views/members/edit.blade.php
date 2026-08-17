@extends('layouts.app')

@section('title', 'Edit Member')

@section('content')
@php
    $inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30';
@endphp

<div class="mx-auto max-w-xl">
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Edit Member</h2>
            <p class="mt-0.5 text-sm text-slate-500">Update member information</p>
        </div>
        <x-button href="{{ route('members.index') }}" color="secondary" size="sm">
            <i class="fa fa-arrow-left"></i> Back
        </x-button>
    </div>

    <x-card>
        <form action="{{ route('members.update', $member->id) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field label="First Name" name="first_name" required>
                    <input type="text" name="first_name" value="{{ old('first_name', $member->first_name) }}" required placeholder="Enter first name" class="{{ $inputClass }}">
                </x-field>

                <x-field label="Last Name" name="last_name" required>
                    <input type="text" name="last_name" value="{{ old('last_name', $member->last_name) }}" required placeholder="Enter last name" class="{{ $inputClass }}">
                </x-field>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field label="Email" name="email">
                    <input type="email" name="email" value="{{ old('email', $member->email) }}" placeholder="member@example.com" class="{{ $inputClass }}">
                </x-field>

                <x-field label="Phone" name="phone">
                    <input type="tel" name="phone" value="{{ old('phone', $member->phone) }}" placeholder="09123456789" class="{{ $inputClass }}">
                </x-field>
            </div>

            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $member->is_active) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <span class="text-sm font-medium text-slate-700">Active Member</span>
            </label>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Member Information</h4>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <span class="text-xs text-slate-500">Member ID</span><br>
                        <span class="text-sm font-semibold text-slate-900">{{ $member->member_number }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500">Total Purchases</span><br>
                        <span class="text-sm font-semibold text-slate-900">₱{{ number_format($member->total_purchases, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500">Purchase Count</span><br>
                        <span class="text-sm font-semibold text-slate-900">{{ $member->purchase_count }}</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-button href="{{ route('members.analytics', $member->id) }}" color="secondary">
                    <i class="fa fa-line-chart"></i> View Analytics
                </x-button>
                <x-button type="submit" color="primary">
                    <i class="fa fa-save"></i> Update Member
                </x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
