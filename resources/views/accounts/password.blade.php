@extends('layouts.app')

@section('title', 'Reset Password')

@section('content')
@php
    $inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30';
@endphp

<div class="mx-auto max-w-xl">
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Reset Password</h2>
            <p class="mt-0.5 text-sm text-slate-500">Set a new password for {{ $account->name }}</p>
        </div>
        <x-button href="{{ route('accounts.index') }}" color="secondary" size="sm">
            <i class="fa fa-arrow-left"></i> Back
        </x-button>
    </div>

    <x-card>
        <form action="{{ route('accounts.password.update', $account) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="text-xs text-slate-500">Account</div>
                <div class="text-sm font-semibold text-slate-900">{{ $account->name }}</div>
                <div class="text-xs text-slate-500">{{ $account->username ?? $account->email }}</div>
            </div>

            <x-field label="New Password" name="password" required>
                <input type="password" id="password" name="password" required autocomplete="new-password" class="{{ $inputClass }}">
            </x-field>

            <x-field label="Confirm New Password" name="password_confirmation" required>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="{{ $inputClass }}">
            </x-field>

            <div class="flex justify-end gap-3 pt-2">
                <x-button href="{{ route('accounts.index') }}" color="secondary">Cancel</x-button>
                <x-button type="submit" color="primary">
                    <i class="fa fa-key"></i> Reset Password
                </x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
