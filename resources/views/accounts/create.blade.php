@extends('layouts.app')

@section('title', 'Add Cashier')

@section('content')
@php
    $inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30';
@endphp

<div class="mx-auto max-w-xl">
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Add Cashier Account</h2>
            <p class="mt-0.5 text-sm text-slate-500">Create a new account for store personnel</p>
        </div>
        <x-button href="{{ route('accounts.index') }}" color="secondary" size="sm">
            <i class="fa fa-arrow-left"></i> Back
        </x-button>
    </div>

    <x-card>
        <form action="{{ route('accounts.store') }}" method="POST" class="space-y-5">
            @csrf

            <x-field label="Username" name="username" required>
                <input type="text" id="username" name="username" value="{{ old('username') }}" required autocomplete="off" placeholder="e.g. cashier.juan" class="{{ $inputClass }}">
            </x-field>

            <x-field label="Display Name" name="name" required>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="Full name" class="{{ $inputClass }}">
            </x-field>

            <x-field label="Email Address" name="email">
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Optional" class="{{ $inputClass }}">
                <p class="mt-1.5 text-xs text-slate-500">Optional. The username is used to sign in.</p>
            </x-field>

            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="flex items-center gap-2 text-sm text-slate-600">
                    <i class="fa fa-shield text-brand-600"></i>
                    Role: <span class="font-semibold text-slate-900">Cashier</span>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field label="Password" name="password" required>
                    <input type="password" id="password" name="password" required autocomplete="new-password" class="{{ $inputClass }}">
                </x-field>

                <x-field label="Confirm Password" name="password_confirmation" required>
                    <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="{{ $inputClass }}">
                </x-field>
            </div>

            <input type="hidden" name="is_active" value="0">
            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <span class="text-sm font-medium text-slate-700">Active Account</span>
            </label>

            <div class="flex justify-end gap-3 pt-2">
                <x-button href="{{ route('accounts.index') }}" color="secondary">Cancel</x-button>
                <x-button type="submit" color="primary">
                    <i class="fa fa-user-plus"></i> Create Account
                </x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
