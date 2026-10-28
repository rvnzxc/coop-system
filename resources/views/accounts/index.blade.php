@extends('layouts.app')

@section('title', 'Accounts')

@section('content')
@php
    $inputClass = 'h-[42px] w-full rounded-lg border border-slate-300 bg-white px-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-[3px] focus:ring-brand-500/20';
    $labelClass = 'mb-1.5 block text-[13px] font-semibold text-slate-900';
    $cashiers = $accounts->where('role', 'cashier')->values();
    $currentUser = Auth::user();
    $totalCashiers = $cashiers->count();
    $activeCashiers = $cashiers->where('is_active', true)->count();
    $disabledCashiers = $totalCashiers - $activeCashiers;
@endphp

<div class="mx-auto max-w-7xl">
    {{-- Toast container --}}
    
    {{-- Page header --}}
    <div class="mb-6">
        <h2 class="text-[22px] font-bold text-slate-900">Accounts</h2>
        <p class="mt-0.5 text-sm text-slate-500">Manage cashier accounts and your own administrator profile</p>
    </div>

    {{-- Summary stats --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-base text-brand-600">
                <x-icon name="users" class="h-5 w-5" />
            </div>
            <div class="min-w-0">
                <div id="statTotalCashiers" class="truncate text-xl font-bold text-slate-900">{{ $totalCashiers }}</div>
                <div class="text-xs text-slate-500">Cashier Accounts</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-base text-emerald-600">
                <x-icon name="check-circle" class="h-5 w-5" />
            </div>
            <div class="min-w-0">
                <div id="statActiveCashiers" class="truncate text-xl font-bold text-slate-900">{{ $activeCashiers }}</div>
                <div class="text-xs text-slate-500">Active</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-base text-red-600">
                <x-icon name="exclamation-circle" class="h-5 w-5" />
            </div>
            <div class="min-w-0">
                <div id="statDisabledCashiers" class="truncate text-xl font-bold text-slate-900">{{ $disabledCashiers }}</div>
                <div class="text-xs text-slate-500">Disabled</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- My account --}}
        <div class="xl:col-span-1">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">My Account</h3>
                    <x-button type="button" color="secondary" size="sm" class="!h-[30px] !px-3 !text-[13px]" onclick="openModal('selfAccountModal')">
                        <x-icon name="edit" class="h-3 w-3" /> Edit
                    </x-button>
                </div>
                <div class="p-5">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-brand-600 text-lg font-bold text-white">
                            {{ mb_strtoupper(mb_substr($currentUser->name ?: $currentUser->username, 0, 2)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-base font-semibold text-slate-900">{{ $currentUser->name }}</p>
                            <p class="truncate text-sm text-slate-500">{{ '@'.($currentUser->username ?: $currentUser->email) }}</p>
                            <div class="mt-2">
                                <x-badge color="green"><x-icon name="circle" class="h-1.5 w-1.5" /> Administrator</x-badge>
                            </div>
                        </div>
                    </div>

                    <dl class="mt-5 space-y-3 border-t border-slate-100 pt-4 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Username</dt>
                            <dd class="truncate text-right font-medium text-slate-900">{{ $currentUser->username ?: '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Display name</dt>
                            <dd class="truncate text-right font-medium text-slate-900">{{ $currentUser->name }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Role</dt>
                            <dd class="text-right font-medium text-slate-900">Admin</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Member since</dt>
                            <dd class="text-right font-medium text-slate-900">{{ $currentUser->created_at ? $currentUser->created_at->format('M d, Y') : '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Cashier accounts --}}
        <div class="xl:col-span-2">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Cashier Accounts</h3>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $totalCashiers }} account{{ $totalCashiers === 1 ? '' : 's' }}</p>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <form onsubmit="event.preventDefault(); filterCashiers();" class="flex gap-2">
                            <div class="relative">
                                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <input type="text" id="cashierSearch" placeholder="Search cashiers..." autocomplete="off"
                                    class="h-[38px] w-full rounded-lg border border-slate-300 bg-white pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-[3px] focus:ring-brand-500/20 sm:w-56">
                            </div>
                            <x-button type="submit" color="secondary" class="!h-[38px] !px-3.5 !text-[13px]">Search</x-button>
                        </form>
                        <x-button id="addCashierButton" type="button" color="primary" class="!h-9 !px-4 !text-sm focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-brand-700" onclick="openModal('addCashierModal')">
                            <x-icon name="user-plus" class="h-4 w-4" /> Add Cashier
                        </x-button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-5 py-3.5 font-semibold">Name</th>
                                <th class="px-5 py-3.5 font-semibold">Username</th>
                                <th class="px-5 py-3.5 font-semibold">Status</th>
                                <th class="px-5 py-3.5 font-semibold">Date Created</th>
                                <th class="px-5 py-3.5 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($cashiers as $cashier)
                                <tr class="transition-colors hover:bg-slate-50"
                                    data-cashier-row
                                    data-search="{{ mb_strtolower($cashier->name.' '.$cashier->username) }}"
                                    data-cashier-id="{{ $cashier->id }}"
                                    data-cashier-name="{{ $cashier->name }}"
                                    data-cashier-username="{{ $cashier->username }}">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#EEF2F1] text-[11px] font-bold text-slate-700">
                                                {{ mb_strtoupper(mb_substr($cashier->name, 0, 2)) }}
                                            </div>
                                            <span class="font-medium text-slate-900">{{ $cashier->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-slate-600">{{ $cashier->username ?: '—' }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-2">
                                            <form action="{{ route('accounts.status', $cashier) }}" method="POST" class="inline-flex">
                                                @csrf
                                                <label class="relative inline-flex cursor-pointer items-center" title="{{ $cashier->is_active ? 'Disable account' : 'Enable account' }}">
                                                    <input type="checkbox" name="is_active" value="1" data-status-toggle class="peer sr-only" {{ $cashier->is_active ? 'checked' : '' }}>
                                                    <span class="relative h-5 w-9 rounded-full bg-slate-300 transition-colors after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-brand-600 peer-checked:after:translate-x-4 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-600"></span>
                                                    <span class="sr-only">Toggle account status</span>
                                                </label>
                                            </form>
                                            <span data-status-pill class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $cashier->is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-red-50 text-red-700 ring-red-600/20' }}">
                                                <span class="h-1.5 w-1.5 rounded-full {{ $cashier->is_active ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                                {{ $cashier->is_active ? 'Active' : 'Disabled' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $cashier->created_at ? $cashier->created_at->format('M d, Y') : '—' }}</td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            <x-button type="button" color="secondary" size="sm" class="!h-[34px] !px-3 !text-[13px]" onclick="openEditCashier(this)">
                                                <x-icon name="edit" class="h-3 w-3" /> Edit
                                            </x-button>
                                            <x-button type="button" color="dangerGhost" size="sm" class="!h-[34px] !px-3 !text-[13px]" onclick="openResetPassword(this)">
                                                <x-icon name="id-card" class="h-3 w-3" /> Reset Password
                                            </x-button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            <tr id="cashierEmptyRow" class="{{ $cashiers->isEmpty() ? '' : 'hidden' }}">
                                <td colspan="5" class="px-5 py-16 text-center text-sm text-slate-500">
                                    No cashiers found
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add cashier modal --}}
<div id="addCashierModal" class="fixed inset-0 z-50 items-center justify-center bg-slate-900/50 p-4" style="display: none;" onclick="if (event.target === this) closeModal('addCashierModal')">
    <div class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white p-6 shadow-[0_20px_40px_rgba(0,0,0,.18)]">
        <button type="button" onclick="closeModal('addCashierModal')" class="absolute right-6 top-6 flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600" aria-label="Close">&times;</button>
        <h3 class="text-base font-semibold text-slate-900">Add Cashier</h3>
        <p class="mt-1 text-sm text-slate-500">Create a new cashier account. The role is always cashier.</p>

        <form id="addCashierForm" action="{{ route('accounts.store') }}" method="POST" class="mt-5 space-y-4" onsubmit="return handleFormSubmit(this)">
            @csrf
            <input type="hidden" name="_form" value="add_cashier">

            <div>
                <label for="addCashierName" class="{{ $labelClass }}">Full name <span class="text-red-500">*</span></label>
                <input type="text" id="addCashierName" name="name" value="{{ old('_form') === 'add_cashier' ? old('name') : '' }}" required maxlength="255" class="{{ $inputClass }}">
                @error('name')
                    <p class="form-error mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="addCashierUsername" class="{{ $labelClass }}">Username <span class="text-red-500">*</span></label>
                <input type="text" id="addCashierUsername" name="username" value="{{ old('_form') === 'add_cashier' ? old('username') : '' }}" required maxlength="64" pattern="[A-Za-z0-9._-]+" class="{{ $inputClass }}" autocomplete="off">
                <p class="mt-1 text-xs text-slate-400">Letters, numbers, dots, underscores and hyphens only.</p>
                @error('username')
                    <p class="form-error mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="addCashierPassword" class="{{ $labelClass }}">Password <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="password" id="addCashierPassword" name="password" required minlength="8" class="{{ $inputClass }} pr-16" autocomplete="new-password">
                    <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded px-2 py-1 text-xs font-semibold text-slate-500 transition-colors hover:bg-slate-100" onclick="togglePassword('addCashierPassword', this)">Show</button>
                </div>
                <p class="mt-1 text-xs text-slate-400">Minimum 8 characters.</p>
                @error('password')
                    <p class="form-error mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="addCashierPasswordConfirmation" class="{{ $labelClass }}">Confirm password <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="password" id="addCashierPasswordConfirmation" name="password_confirmation" required minlength="8" class="{{ $inputClass }} pr-16" autocomplete="new-password">
                    <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded px-2 py-1 text-xs font-semibold text-slate-500 transition-colors hover:bg-slate-100" onclick="togglePassword('addCashierPasswordConfirmation', this)">Show</button>
                </div>
            </div>

            <div class="flex gap-3 pt-6">
                <x-button type="button" color="secondary" class="!h-[38px] flex-1 !text-sm" onclick="closeModal('addCashierModal')">Cancel</x-button>
                <x-button type="submit" color="primary" class="!h-[38px] flex-1 !text-sm">
                    <x-icon name="user-plus" class="h-3.5 w-3.5" /> Create Cashier
                </x-button>
            </div>
        </form>
    </div>
</div>

{{-- Edit cashier modal --}}
<div id="editCashierModal" class="fixed inset-0 z-50 items-center justify-center bg-slate-900/50 p-4" style="display: none;" onclick="if (event.target === this) closeModal('editCashierModal')">
    <div class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white p-6 shadow-[0_20px_40px_rgba(0,0,0,.18)]">
        <button type="button" onclick="closeModal('editCashierModal')" class="absolute right-6 top-6 flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600" aria-label="Close">&times;</button>
        <h3 class="text-base font-semibold text-slate-900">Edit Cashier</h3>
        <p class="mt-1 text-sm text-slate-500">Update the cashier's details. Leave the password blank to keep it unchanged.</p>

        <form id="editCashierForm" action="" method="POST" class="mt-5 space-y-4" onsubmit="return handleFormSubmit(this)">
            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="edit_cashier">
            <input type="hidden" name="cashier_id" id="editCashierId" value="{{ old('_form') === 'edit_cashier' ? old('cashier_id') : '' }}">

            <div>
                <label for="editCashierName" class="{{ $labelClass }}">Full name <span class="text-red-500">*</span></label>
                <input type="text" id="editCashierName" name="name" required maxlength="255" class="{{ $inputClass }}">
                @error('name')
                    <p class="form-error mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="editCashierUsername" class="{{ $labelClass }}">Username <span class="text-red-500">*</span></label>
                <input type="text" id="editCashierUsername" name="username" required maxlength="64" pattern="[A-Za-z0-9._-]+" class="{{ $inputClass }}" autocomplete="off">
                @error('username')
                    <p class="form-error mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="editCashierPassword" class="{{ $labelClass }}">New password</label>
                <div class="relative">
                    <input type="password" id="editCashierPassword" name="password" minlength="8" class="{{ $inputClass }} pr-16" autocomplete="new-password">
                    <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded px-2 py-1 text-xs font-semibold text-slate-500 transition-colors hover:bg-slate-100" onclick="togglePassword('editCashierPassword', this)">Show</button>
                </div>
                <p class="mt-1 text-xs text-slate-400">Optional. Minimum 8 characters when provided.</p>
                @error('password')
                    <p class="form-error mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="editCashierPasswordConfirmation" class="{{ $labelClass }}">Confirm new password</label>
                <div class="relative">
                    <input type="password" id="editCashierPasswordConfirmation" name="password_confirmation" minlength="8" class="{{ $inputClass }} pr-16" autocomplete="new-password">
                    <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded px-2 py-1 text-xs font-semibold text-slate-500 transition-colors hover:bg-slate-100" onclick="togglePassword('editCashierPasswordConfirmation', this)">Show</button>
                </div>
            </div>

            <div class="flex gap-3 pt-6">
                <x-button type="button" color="secondary" class="!h-[38px] flex-1 !text-sm" onclick="closeModal('editCashierModal')">Cancel</x-button>
                <x-button type="submit" color="primary" class="!h-[38px] flex-1 !text-sm">
                    <x-icon name="save" class="h-3.5 w-3.5" /> Save Changes
                </x-button>
            </div>
        </form>
    </div>
</div>

{{-- Reset password modal --}}
<div id="resetPasswordModal" class="fixed inset-0 z-50 items-center justify-center bg-slate-900/50 p-4" style="display: none;" onclick="if (event.target === this) closeModal('resetPasswordModal')">
    <div class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white p-6 shadow-[0_20px_40px_rgba(0,0,0,.18)]">
        <button type="button" onclick="closeModal('resetPasswordModal')" class="absolute right-6 top-6 flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600" aria-label="Close">&times;</button>
        <h3 class="text-base font-semibold text-slate-900">Reset Password</h3>
        <p class="mt-1 text-sm text-slate-500">Set a new password for <span id="resetCashierName" class="font-semibold text-slate-700">this cashier</span>.</p>

        <form id="resetPasswordForm" action="" method="POST" class="mt-5 space-y-4" onsubmit="return handleFormSubmit(this)">
            @csrf
            <input type="hidden" name="_form" value="reset_password">
            <input type="hidden" name="cashier_id" id="resetCashierId" value="{{ old('_form') === 'reset_password' ? old('cashier_id') : '' }}">

            <div>
                <label for="resetCashierPassword" class="{{ $labelClass }}">New password <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="password" id="resetCashierPassword" name="password" required minlength="8" class="{{ $inputClass }} pr-16" autocomplete="new-password">
                    <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded px-2 py-1 text-xs font-semibold text-slate-500 transition-colors hover:bg-slate-100" onclick="togglePassword('resetCashierPassword', this)">Show</button>
                </div>
                <p class="mt-1 text-xs text-slate-400">Minimum 8 characters.</p>
                @error('password')
                    <p class="form-error mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="resetCashierPasswordConfirmation" class="{{ $labelClass }}">Confirm new password <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="password" id="resetCashierPasswordConfirmation" name="password_confirmation" required minlength="8" class="{{ $inputClass }} pr-16" autocomplete="new-password">
                    <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded px-2 py-1 text-xs font-semibold text-slate-500 transition-colors hover:bg-slate-100" onclick="togglePassword('resetCashierPasswordConfirmation', this)">Show</button>
                </div>
            </div>

            <div class="flex gap-3 pt-6">
                <x-button type="button" color="secondary" class="!h-[38px] flex-1 !text-sm" onclick="closeModal('resetPasswordModal')">Cancel</x-button>
                <x-button type="submit" color="primary" class="!h-[38px] flex-1 !text-sm">
                    <x-icon name="id-card" class="h-3.5 w-3.5" /> Reset Password
                </x-button>
            </div>
        </form>
    </div>
</div>

{{-- Own account modal --}}
<div id="selfAccountModal" class="fixed inset-0 z-50 items-center justify-center bg-slate-900/50 p-4" style="display: none;" onclick="if (event.target === this) closeModal('selfAccountModal')">
    <div class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white p-6 shadow-[0_20px_40px_rgba(0,0,0,.18)]">
        <button type="button" onclick="closeModal('selfAccountModal')" class="absolute right-6 top-6 flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600" aria-label="Close">&times;</button>
        <h3 class="text-base font-semibold text-slate-900">Edit My Account</h3>
        <p class="mt-1 text-sm text-slate-500">Update your administrator details. Changing your password requires your current password.</p>

        <form id="selfAccountForm" action="{{ route('accounts.me.update') }}" method="POST" class="mt-5 space-y-4" onsubmit="return handleFormSubmit(this)">
            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="self_account">
            <input type="hidden" name="_redirect" value="index">

            <div>
                <label for="selfAccountName" class="{{ $labelClass }}">Display name <span class="text-red-500">*</span></label>
                <input type="text" id="selfAccountName" name="name" value="{{ old('_form') === 'self_account' ? old('name') : $currentUser->name }}" required maxlength="255" class="{{ $inputClass }}">
                @error('name')
                    <p class="form-error mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="selfAccountUsername" class="{{ $labelClass }}">Username <span class="text-red-500">*</span></label>
                <input type="text" id="selfAccountUsername" name="username" value="{{ old('_form') === 'self_account' ? old('username') : $currentUser->username }}" required maxlength="64" pattern="[A-Za-z0-9._-]+" class="{{ $inputClass }}" autocomplete="off">
                @error('username')
                    <p class="form-error mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="border-t border-slate-100 pt-4">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">Change password (optional)</p>

                <div>
                    <label for="selfCurrentPassword" class="{{ $labelClass }}">Current password</label>
                    <div class="relative">
                        <input type="password" id="selfCurrentPassword" name="current_password" class="{{ $inputClass }} pr-16" autocomplete="current-password">
                        <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded px-2 py-1 text-xs font-semibold text-slate-500 transition-colors hover:bg-slate-100" onclick="togglePassword('selfCurrentPassword', this)">Show</button>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">Required only when setting a new password.</p>
                    @error('current_password')
                        <p class="form-error mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4">
                    <label for="selfNewPassword" class="{{ $labelClass }}">New password</label>
                    <div class="relative">
                        <input type="password" id="selfNewPassword" name="password" minlength="8" class="{{ $inputClass }} pr-16" autocomplete="new-password">
                        <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded px-2 py-1 text-xs font-semibold text-slate-500 transition-colors hover:bg-slate-100" onclick="togglePassword('selfNewPassword', this)">Show</button>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">Minimum 8 characters when provided.</p>
                    @error('password')
                        <p class="form-error mt-1.5 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-4">
                    <label for="selfNewPasswordConfirmation" class="{{ $labelClass }}">Confirm new password</label>
                    <div class="relative">
                        <input type="password" id="selfNewPasswordConfirmation" name="password_confirmation" minlength="8" class="{{ $inputClass }} pr-16" autocomplete="new-password">
                        <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded px-2 py-1 text-xs font-semibold text-slate-500 transition-colors hover:bg-slate-100" onclick="togglePassword('selfNewPasswordConfirmation', this)">Show</button>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-6">
                <x-button type="button" color="secondary" class="!h-[38px] flex-1 !text-sm" onclick="closeModal('selfAccountModal')">Cancel</x-button>
                <x-button type="submit" color="primary" class="!h-[38px] flex-1 !text-sm">
                    <x-icon name="save" class="h-3.5 w-3.5" /> Save Changes
                </x-button>
            </div>
        </form>
    </div>
</div>

<script>
    const ACCOUNT_ROUTES = {
        updateCashier: @json(route('accounts.update', ['user' => '__ID__'])),
        resetCashierPassword: @json(route('accounts.password.update', ['user' => '__ID__'])),
    };

    const MODAL_IDS = ['addCashierModal', 'editCashierModal', 'resetPasswordModal', 'selfAccountModal'];

    document.addEventListener('DOMContentLoaded', function () {
        @if($errors->any())
            showToast(@json($errors->first()), 'error');

            @if(old('_form') === 'add_cashier')
                openModal('addCashierModal');
            @elseif(old('_form') === 'edit_cashier')
                openEditCashierById(@json(old('cashier_id')), @json(old('name')), @json(old('username')));
            @elseif(old('_form') === 'reset_password')
                openResetPasswordById(@json(old('cashier_id')), null);
            @elseif(old('_form') === 'self_account')
                openModal('selfAccountModal');
            @endif
        @endif

        const search = document.getElementById('cashierSearch');
        if (search) {
            search.addEventListener('input', filterCashiers);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;

        const open = MODAL_IDS.map(function (id) {
            return document.getElementById(id);
        }).filter(function (modal) {
            return modal && modal.style.display === 'flex';
        });

        if (open.length) {
            closeModal(open[open.length - 1].id);
            event.preventDefault();
        }
    });

    function openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        modal.style.display = 'flex';

        const focusable = modal.querySelector('input:not([type="hidden"]), select, textarea');
        if (focusable) {
            window.setTimeout(function () {
                focusable.focus();
            }, 50);
        }
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;

        modal.style.display = 'none';
        modal.querySelectorAll('.form-error').forEach(function (el) {
            el.remove();
        });
    }

    function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);
        if (!input) return;

        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.textContent = show ? 'Hide' : 'Show';
    }

    function handleFormSubmit(form) {
        if (form.dataset.submitting === '1') {
            return false;
        }

        form.dataset.submitting = '1';

        const submit = form.querySelector('button[type="submit"]');
        if (submit) {
            submit.disabled = true;
            submit.textContent = 'Saving...';
        }

        return true;
    }

    function openEditCashier(button) {
        const row = button.closest('tr');
        if (!row) return;

        openEditCashierById(row.dataset.cashierId, row.dataset.cashierName, row.dataset.cashierUsername);
    }

    function openEditCashierById(id, name, username) {
        if (!id) return;

        const form = document.getElementById('editCashierForm');
        if (!form) return;

        form.action = ACCOUNT_ROUTES.updateCashier.replace('__ID__', encodeURIComponent(id));
        document.getElementById('editCashierId').value = id;
        document.getElementById('editCashierName').value = name || '';
        document.getElementById('editCashierUsername').value = username || '';
        document.getElementById('editCashierPassword').value = '';
        document.getElementById('editCashierPasswordConfirmation').value = '';

        openModal('editCashierModal');
    }

    function openResetPassword(button) {
        const row = button.closest('tr');
        if (!row) return;

        openResetPasswordById(row.dataset.cashierId, row.dataset.cashierName);
    }

    function openResetPasswordById(id, name) {
        if (!id) return;

        const form = document.getElementById('resetPasswordForm');
        if (!form) return;

        form.action = ACCOUNT_ROUTES.resetCashierPassword.replace('__ID__', encodeURIComponent(id));
        document.getElementById('resetCashierId').value = id;
        document.getElementById('resetCashierPassword').value = '';
        document.getElementById('resetCashierPasswordConfirmation').value = '';

        const nameTarget = document.getElementById('resetCashierName');
        if (nameTarget) {
            nameTarget.textContent = name || 'this cashier';
        }

        openModal('resetPasswordModal');
    }

    function filterCashiers() {
        const input = document.getElementById('cashierSearch');
        const query = input ? input.value.trim().toLowerCase() : '';
        let visible = 0;

        document.querySelectorAll('tr[data-cashier-row]').forEach(function (row) {
            const match = !query || (row.dataset.search || '').includes(query);
            row.classList.toggle('hidden', !match);

            if (match) {
                visible++;
            }
        });

        const empty = document.getElementById('cashierEmptyRow');
        if (empty) {
            empty.classList.toggle('hidden', visible > 0);
        }
    }

    function updateStats() {
        const rows = document.querySelectorAll('tr[data-cashier-row]');
        let active = 0;

        rows.forEach(function (row) {
            const toggle = row.querySelector('[data-status-toggle]');
            if (toggle && toggle.checked) {
                active++;
            }
        });

        const total = rows.length;
        const totalEl = document.getElementById('statTotalCashiers');
        const activeEl = document.getElementById('statActiveCashiers');
        const disabledEl = document.getElementById('statDisabledCashiers');

        if (totalEl) totalEl.textContent = total;
        if (activeEl) activeEl.textContent = active;
        if (disabledEl) disabledEl.textContent = total - active;
    }

    document.addEventListener('change', function (event) {
        const toggle = event.target.closest('[data-status-toggle]');
        if (!toggle) return;

        const form = toggle.closest('form');
        const row = toggle.closest('tr');
        const pill = row ? row.querySelector('[data-status-pill]') : null;
        const previous = !toggle.checked;

        if (!form || !row) return;

        const token = form.querySelector('input[name="_token"]');
        const body = new URLSearchParams();
        body.set('_token', token ? token.value : '');
        body.set('is_active', toggle.checked ? '1' : '0');

        toggle.disabled = true;
        renderStatusPill(pill, toggle.checked);
        updateStats();

        fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            },
            body: body.toString(),
            credentials: 'same-origin',
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('Request failed');
            }

            const username = row.dataset.cashierUsername || 'Account';
            showToast(username + (toggle.checked ? ' has been activated.' : ' has been disabled.'), 'success');
        }).catch(function () {
            toggle.checked = previous;
            renderStatusPill(pill, previous);
            updateStats();
            showToast('Unable to update the account status. Please try again.', 'error');
        }).finally(function () {
            toggle.disabled = false;
        });
    });

    function renderStatusPill(pill, active) {
        if (!pill) return;

        pill.className = 'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset ' +
            (active
                ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
                : 'bg-red-50 text-red-700 ring-red-600/20');

        const dot = pill.querySelector('span');
        if (dot) {
            dot.className = 'h-1.5 w-1.5 rounded-full ' + (active ? 'bg-emerald-500' : 'bg-red-500');
        }

        const label = Array.from(pill.childNodes).find(function (node) {
            return node.nodeType === Node.TEXT_NODE && node.textContent.trim() !== '';
        });
        if (label) {
            label.textContent = active ? ' Active' : ' Disabled';
        } else {
            pill.append(document.createTextNode(active ? ' Active' : ' Disabled'));
        }
    }

</script>
@endsection
