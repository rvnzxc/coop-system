@extends('layouts.app')

@section('title', 'My Account')

@section('content')
@php
    $inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30';
@endphp

<div class="mx-auto max-w-xl">
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-900">My Account</h2>
            <p class="mt-0.5 text-sm text-slate-500">Update your administrator profile and password</p>
        </div>
        <x-button href="{{ route('accounts.index') }}" color="secondary" size="sm">
            <x-icon name="arrow-left" class="h-3.5 w-3.5" /> Back
        </x-button>
    </div>

    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            <x-icon name="check-circle" class="h-4 w-4" />
            {{ session('success') }}
        </div>
    @endif

    <x-card>
        <form action="{{ route('accounts.me.update') }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="flex flex-wrap items-center gap-2 text-sm text-slate-600">
                    <x-icon name="user-circle" class="h-4 w-4 text-brand-600" />
                    Role: <span class="font-semibold text-slate-900">{{ ucfirst($account->role) }}</span>
                    @if($account->is_active)
                        <x-badge color="green"><x-icon name="circle" class="h-1.5 w-1.5" /> Active</x-badge>
                    @else
                        <x-badge color="red"><x-icon name="circle" class="h-1.5 w-1.5" /> Inactive</x-badge>
                    @endif
                </div>
            </div>

            <x-field label="Username" name="username" required>
                <input type="text" id="username" name="username" value="{{ old('username', $account->username) }}" required autocomplete="username" class="{{ $inputClass }}">
            </x-field>

            <x-field label="Display Name" name="name" required>
                <input type="text" id="name" name="name" value="{{ old('name', $account->name) }}" required class="{{ $inputClass }}">
            </x-field>

            <x-field label="Email Address" name="email">
                <input type="email" id="email" name="email" value="{{ old('email', $account->email) }}" placeholder="Optional" class="{{ $inputClass }}">
            </x-field>

            <div class="border-t border-slate-100 pt-5">
                <h4 class="text-sm font-semibold text-slate-900">Change Password</h4>
                <p class="mt-0.5 text-xs text-slate-500">Leave the password fields blank to keep your current password.</p>

                <div class="mt-4 space-y-5">
                    <x-field label="Current Password" name="current_password">
                        <input type="password" id="current_password" name="current_password" autocomplete="current-password" class="{{ $inputClass }}">
                    </x-field>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field label="New Password" name="password">
                            <input type="password" id="password" name="password" autocomplete="new-password" class="{{ $inputClass }}">
                        </x-field>

                        <x-field label="Confirm New Password" name="password_confirmation">
                            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" class="{{ $inputClass }}">
                        </x-field>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-button href="{{ route('accounts.index') }}" color="secondary">Cancel</x-button>
                <x-button type="submit" color="primary">
                    <x-icon name="save" class="h-4 w-4" /> Save Changes
                </x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
