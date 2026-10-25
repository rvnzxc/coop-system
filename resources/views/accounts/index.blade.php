@extends('layouts.app')

@section('title', 'Accounts')

@section('content')
@php
    $inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30';
    $labelClass = 'mb-1.5 block text-sm font-medium text-slate-700';
    $totalCashiers = $cashiers->count();
    $activeCashiers = $cashiers->where('is_active', true)->count();
    $disabledCashiers = $totalCashiers - $activeCashiers;
@endphp

<div class="mx-auto max-w-7xl">
    {{-- Toast container --}}
    <div id="toastContainer" class="pointer-events-none fixed right-4 top-20 z-[60] flex w-full max-w-sm flex-col gap-2"></div>

    {{-- Page header --}}
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900">Accounts</h2>
        <p class="mt-0.5 text-sm text-slate-500">Manage cashier accounts and your own administrator profile</p>
    </div>

    {{-- Summary stats --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-base text-brand-600">
                <x-icon name="users" class="h-5 w-5" />
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">{{ $totalCashiers }}</div>
                <div class="text-xs text-slate-500">Cashier Accounts</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-base text-emerald-600">
                <x-icon name="check-circle" class="h-5 w-5" />
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">{{ $activeCashiers }}</div>
                <div class="text-xs text-slate-500">Active</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-base text-red-600">
                <x-icon name="exclamation-circle" class="h-5 w-5" />
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">{{ $disabledCashiers }}</div>
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
                    <x-button type="button" color="secondary" size="sm" onclick="openModal('selfAccountModal')">
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
                            <dd class="truncate font-medium text-slate-900">{{ $currentUser->username ?: '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Display name</dt>
                            <dd class="truncate font-medium text-slate-900">{{ $currentUser->name }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Role</dt>
                            <dd class="font-medium text-slate-900">Admin</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Member since</dt>
                            <dd class="font-medium text-slate-900">{{ $currentUser->created_at ? $currentUser->created_at->format('M d, Y') : '—' }}</dd>
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
                        <form action="{{ route('accounts.index') }}" method="GET" class="flex gap-2">
                            <div class="relative">
                                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                                <input type="text" name="search" value="{{ $search }}" placeholder="Search cashiers..." class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 sm:w-56">
                            </div>
                            <x-button type="submit" color="secondary" size="sm">Search</x-button>
                        </form>
                        <x-button type="button" color="primary" onclick="openModal('addCashierModal')">
                            <x-icon name="user-plus" class="h-3.5 w-3.5" /> Add Cashier
                        </x-button>
                    </div>
                </div>

                @if($search)
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-2">
                        <span class="text-xs text-slate-500">Showing results for "{{ $search }}"</span>
                        <a href="{{ route('accounts.index') }}" class="text-xs font-medium text-slate-500 transition-colors hover:text-brand-600">
                            <x-icon name="times" class="h-3 w-3" /> Clear search
                        </a>
                    </div>
                @endif

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
                            @forelse($cashiers as $cashier)
                                <tr class="transition-colors hover:bg-slate-50"
                                    data-cashier-id="{{ $cashier->id }}"
                                    data-cashier-name="{{ $cashier->name }}"
                                    data-cashier-username="{{ $cashier->username }}">
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">
                                                {{ mb_strtoupper(mb_substr($cashier->name, 0, 2)) }}
                                            </div>
                                            <span class="font-medium text-slate-900">{{ $cashier->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-600">{{ $cashier->username ?: '—' }}</td>
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-2">
                                            <form action="{{ route('accounts.cashiers.status', $cashier) }}" method="POST" class="inline-flex">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="is_active" value="0">
                                                <label class="relative inline-flex cursor-pointer items-center" title="{{ $cashier->is_active ? 'Disable account' : 'Enable account' }}">
                                                    <input type="checkbox" name="is_active" value="1" class="peer sr-only" {{ $cashier->is_active ? 'checked' : '' }} onchange="this.form.submit()">
                                                    <span class="relative h-5 w-9 rounded-full bg-slate-300 transition-colors after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-brand-600 peer-checked:after:translate-x-4 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-600"></span>
                                                    <span class="sr-only">Toggle account status</span>
                                                </label>
                                            </form>
                                            @if($cashier->is_active)
                                                <x-badge color="green"><x-icon name="circle" class="h-1.5 w-1.5" /> Active</x-badge>
                                            @else
                                                <x-badge color="red"><x-icon name="circle" class="h-1.5 w-1.5" /> Disabled</x-badge>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-slate-500">{{ $cashier->created_at ? $cashier->created_at->format('M d, Y') : '—' }}</td>
                                    <td class="whitespace-nowrap px-5 py-3.5">
                                        <div class="flex items-center justify-end gap-2">
                                            <x-button type="button" color="secondary" size="sm" onclick="openEditCashier(this)">
                                                <x-icon name="edit" class="h-3 w-3" /> Edit
                                            </x-button>
                                            <x-button type="button" color="dangerGhost" size="sm" onclick="openResetPassword(this)">
                                                <x-icon name="save" class="h-3 w-3" /> Reset Password
                                            </x-button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                                <x-icon name="users" class="h-6 w-6" />
                                            </div>
                                            <p class="text-base font-semibold text-slate-700">{{ $search ? 'No cashiers match your search' : 'No cashier accounts yet' }}</p>
                                            <p class="mt-1 text-sm text-slate-500">{{ $search ? 'Try a different search term.' : 'Create the first cashier account to get started.' }}</p>
                                            @unless($search)
                                                <x-button type="button" color="primary" class="mt-5" onclick="openModal('addCashierModal')">
                                                    <x-icon name="user-plus" class="h-3.5 w-3.5" /> Add Cashier
                                                </x-button>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add cashier modal --}}
<div id="addCashierModal" class="fixed inset-0 z-50 items-center justify-center bg-slate-900/50 p-4" style="display: none;" onclick="if (event.target === this) closeModal('addCashierModal')">
    <div class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-xl bg-white p-6 shadow-xl">
        <button type="button" onclick="closeModal('addCashierModal')" class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600" aria-label="Close">&times;</button>
        <h3 class="text-base font-bold text-slate-900">Add Cashier</h3>
        <p class="mt-1 text-sm text-slate-500">Create a new cashier account. The role is always cashier.</p>

        <form id="addCashierForm" action="{{ route('accounts.cashiers.store') }}" method="POST" class="mt-5 space-y-4" onsubmit="return handleFormSubmit(this)">
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

            <div class="flex gap-3 pt-1">
                <x-button type="button" color="secondary" class="flex-1" onclick="closeModal('addCashierModal')">Cancel</x-button>
                <x-button type="submit" color="primary" class="flex-1">
                    <x-icon name="user-plus" class="h-3.5 w-3.5" /> Create Cashier
                </x-button>
            </div>
        </form>
    </div>
</div>

{{-- Edit cashier modal --}}
<div id="editCashierModal" class="fixed inset-0 z-50 items-center justify-center bg-slate-900/50 p-4" style="display: none;" onclick="if (event.target === this) closeModal('editCashierModal')">
    <div class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-xl bg-white p-6 shadow-xl">
        <button type="button" onclick="closeModal('editCashierModal')" class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600" aria-label="Close">&times;</button>
        <h3 class="text-base font-bold text-slate-900">Edit Cashier</h3>
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

            <div class="flex gap-3 pt-1">
                <x-button type="button" color="secondary" class="flex-1" onclick="closeModal('editCashierModal')">Cancel</x-button>
                <x-button type="submit" color="primary" class="flex-1">
                    <x-icon name="save" class="h-3.5 w-3.5" /> Save Changes
                </x-button>
            </div>
        </form>
    </div>
</div>

{{-- Reset password modal --}}
<div id="resetPasswordModal" class="fixed inset-0 z-50 items-center justify-center bg-slate-900/50 p-4" style="display: none;" onclick="if (event.target === this) closeModal('resetPasswordModal')">
    <div class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-xl bg-white p-6 shadow-xl">
        <button type="button" onclick="closeModal('resetPasswordModal')" class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600" aria-label="Close">&times;</button>
        <h3 class="text-base font-bold text-slate-900">Reset Password</h3>
        <p class="mt-1 text-sm text-slate-500">Set a new password for <span id="resetCashierName" class="font-semibold text-slate-700">this cashier</span>.</p>

        <form id="resetPasswordForm" action="" method="POST" class="mt-5 space-y-4" onsubmit="return confirmResetPassword(this)">
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

            <div class="flex gap-3 pt-1">
                <x-button type="button" color="secondary" class="flex-1" onclick="closeModal('resetPasswordModal')">Cancel</x-button>
                <x-button type="submit" color="danger" class="flex-1">
                    <x-icon name="save" class="h-3.5 w-3.5" /> Reset Password
                </x-button>
            </div>
        </form>
    </div>
</div>

{{-- Own account modal --}}
<div id="selfAccountModal" class="fixed inset-0 z-50 items-center justify-center bg-slate-900/50 p-4" style="display: none;" onclick="if (event.target === this) closeModal('selfAccountModal')">
    <div class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-xl bg-white p-6 shadow-xl">
        <button type="button" onclick="closeModal('selfAccountModal')" class="absolute right-4 top-4 flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600" aria-label="Close">&times;</button>
        <h3 class="text-base font-bold text-slate-900">Edit My Account</h3>
        <p class="mt-1 text-sm text-slate-500">Update your administrator details. Changing your password requires your current password.</p>

        <form id="selfAccountForm" action="{{ route('accounts.me.update') }}" method="POST" class="mt-5 space-y-4" onsubmit="return handleFormSubmit(this)">
            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="self_account">

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

            <div class="flex gap-3 pt-1">
                <x-button type="button" color="secondary" class="flex-1" onclick="closeModal('selfAccountModal')">Cancel</x-button>
                <x-button type="submit" color="primary" class="flex-1">
                    <x-icon name="save" class="h-3.5 w-3.5" /> Save Changes
                </x-button>
            </div>
        </form>
    </div>
</div>

<script>
    const ACCOUNT_ROUTES = {
        updateCashier: @json(route('accounts.cashiers.update', ['user' => '__ID__'])),
        resetCashierPassword: @json(route('accounts.cashiers.reset-password', ['user' => '__ID__'])),
    };

    document.addEventListener('DOMContentLoaded', function () {
        @if(session('success'))
            showToast(@json(session('success')), 'success');
        @endif

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
    });

    function openModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'none';
            modal.querySelectorAll('.form-error').forEach(function (el) {
                el.remove();
            });
        }
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
            submit.dataset.originalLabel = submit.textContent.trim();
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
        form.action = ACCOUNT_ROUTES.resetCashierPassword.replace('__ID__', encodeURIComponent(id));
        document.getElementById('resetCashierId').value = id;
        document.getElementById('resetCashierPassword').value = '';
        document.getElementById('resetCashierPasswordConfirmation').value = '';

        const nameTarget = document.getElementById('resetCashierName');
        nameTarget.textContent = name || 'this cashier';

        openModal('resetPasswordModal');
    }

    function confirmResetPassword(form) {
        if (!window.confirm('Reset this cashier\'s password? They will need the new password to sign in.')) {
            return false;
        }

        return handleFormSubmit(form);
    }

    function showToast(message, type) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const success = type === 'success';
        const toast = document.createElement('div');
        toast.className = 'pointer-events-auto flex items-start gap-3 rounded-lg border px-4 py-3 text-sm font-medium shadow-lg transition-all duration-200 ' +
            (success ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700');

        const icon = document.createElement('span');
        icon.className = 'mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-xs font-bold ' +
            (success ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700');
        icon.textContent = success ? '\u2713' : '!';

        const text = document.createElement('span');
        text.className = 'min-w-0 flex-1';
        text.textContent = message;

        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'shrink-0 rounded px-1 text-lg leading-none opacity-60 transition-opacity hover:opacity-100';
        close.textContent = '\u00d7';
        close.setAttribute('aria-label', 'Dismiss');
        close.addEventListener('click', function () {
            toast.remove();
        });

        toast.append(icon, text, close);
        container.appendChild(toast);

        window.setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-4px)';
            window.setTimeout(function () {
                toast.remove();
            }, 200);
        }, 4500);
    }
</script>
@endsection
