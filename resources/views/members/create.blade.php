@extends('layouts.app')

@section('title', 'Add Member')

@section('content')
@php
    $inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30';
@endphp

<div class="mx-auto max-w-xl">
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Add New Member</h2>
            <p class="mt-0.5 text-sm text-slate-500">Register a cooperative member</p>
        </div>
        <x-button href="{{ route('members.index') }}" color="secondary" size="sm">
            <i class="fa fa-arrow-left"></i> Back
        </x-button>
    </div>

    <x-card>
        <form action="{{ route('members.store') }}" method="POST" class="space-y-5">
            @csrf

            <x-field label="First Name" name="first_name" required>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required class="{{ $inputClass }}">
            </x-field>

            <x-field label="Last Name" name="last_name" required>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required class="{{ $inputClass }}">
            </x-field>

            <x-field label="Email" name="email">
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="member@example.com" class="{{ $inputClass }}">
            </x-field>

            <x-field label="Phone" name="phone">
                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="09123456789" class="{{ $inputClass }}">
            </x-field>

            <div class="flex gap-3 pt-2">
                <x-button type="submit" color="primary" class="flex-1">
                    <i class="fa fa-user-plus"></i> Add Member
                </x-button>
                <x-button type="button" color="secondary" onclick="location.href='{{ route('members.index') }}'">
                    Cancel
                </x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
