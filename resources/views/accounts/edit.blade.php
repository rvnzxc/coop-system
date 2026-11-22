@extends('layouts.app')

@section('title', 'Edit Account')

@section('content')
@php
    $inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30';
@endphp

<div class="mx-auto max-w-xl">
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Edit Account</h2>
            <p class="mt-0.5 text-sm text-slate-500">Update account details for {{ $account->name }}</p>
        </div>
        <x-button href="{{ route('accounts.index') }}" color="secondary" size="sm">
            <x-icon name="arrow-left" class="h-3.5 w-3.5" /> Back
        </x-button>
    </div>

    <x-card>
        <form action="{{ route('accounts.update', $account) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="flex items-center gap-2 text-sm text-slate-600">
                    <x-icon name="user-circle" class="h-4 w-4 text-brand-600" />
                    Role: <span class="font-semibold text-slate-900">{{ ucfirst($account->role) }}</span>
                </div>
            </div>

            <x-field label="Username" name="username" required>
                <input type="text" id="username" name="username" value="{{ old('username', $account->username) }}" required autocomplete="off" class="{{ $inputClass }}">
            </x-field>

            <x-field label="Display Name" name="name" required>
                <input type="text" id="name" name="name" value="{{ old('name', $account->name) }}" required class="{{ $inputClass }}">
            </x-field>

            <x-field label="Email Address" name="email">
                <input type="email" id="email" name="email" value="{{ old('email', $account->email) }}" placeholder="Optional" class="{{ $inputClass }}">
            </x-field>

            <input type="hidden" name="is_active" value="0">
            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $account->is_active) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <span class="text-sm font-medium text-slate-700">Active Account</span>
            </label>

            <div class="border-t border-slate-100 pt-5">
                <h4 class="text-sm font-semibold text-slate-900">Reset Password</h4>
                <p class="mt-0.5 text-xs text-slate-500">Leave these fields blank to keep the current password.</p>

                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                    <x-field label="New Password" name="password">
                        <input type="password" id="password" name="password" autocomplete="new-password" class="{{ $inputClass }}">
                    </x-field>

                    <x-field label="Confirm New Password" name="password_confirmation">
                        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" class="{{ $inputClass }}">
                    </x-field>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-button href="{{ route('accounts.index') }}" color="secondary">Cancel</x-button>
                <x-button type="submit" color="primary">
                    <x-icon name="save" class="h-4 w-4" /> Update Account
                </x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
