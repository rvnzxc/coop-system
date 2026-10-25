@extends('layouts.app')

@section('title', 'Accounts')

@section('content')
@php
    $cashiers = $accounts->where('role', 'cashier')->values();
    $cashierTotal = $cashiers->count();
    $activeCashiers = $cashiers->where('is_active', true)->count();
    $disabledCashiers = $cashierTotal - $activeCashiers;
    $admin = Auth::user();

    $initials = function ($name) {
        $parts = preg_split('/\s+/', trim((string) $name));
        $letters = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $letters .= mb_substr($part, 0, 1);
        }

        return mb_strtoupper($letters ?: '?');
    };
@endphp

<div class="mx-auto max-w-7xl">
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            <x-icon name="check-circle" class="h-4 w-4" />
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            <x-icon name="exclamation-circle" class="h-4 w-4" />
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <div class="flex items-center gap-3 font-medium">
                <x-icon name="exclamation-circle" class="h-4 w-4" />
                Please fix the following:
            </div>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900">Accounts</h2>
        <p class="mt-0.5 text-sm text-slate-500">Manage cashier accounts and your own administrator profile</p>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-base text-brand-600">
                <x-icon name="users" class="h-4 w-4" />
            </div>
            <div class="min-w-0">
                <div class="truncate text-2xl font-bold leading-none text-slate-900">{{ $cashierTotal }}</div>
                <div class="mt-1.5 text-xs text-slate-500">Cashier Accounts</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-base text-emerald-600">
                <x-icon name="check-circle" class="h-4 w-4" />
            </div>
            <div class="min-w-0">
                <div class="truncate text-2xl font-bold leading-none text-slate-900">{{ $activeCashiers }}</div>
                <div class="mt-1.5 text-xs text-slate-500">Active</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-red-50 text-base text-red-600">
                <x-icon name="exclamation-triangle" class="h-4 w-4" />
            </div>
            <div class="min-w-0">
                <div class="truncate text-2xl font-bold leading-none text-slate-900">{{ $disabledCashiers }}</div>
                <div class="mt-1.5 text-xs text-slate-500">Disabled</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-1">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-semibold text-slate-900">My Account</h3>
                    <x-button href="{{ route('accounts.me') }}" color="secondary" size="sm"
                        data-account-modal-url="{{ route('accounts.me') }}"
                        data-account-modal-title="Edit My Account"
                        data-account-modal-subtitle="Update your administrator profile and password">
                        <x-icon name="edit" class="h-4 w-4" /> Edit
                    </x-button>
                </div>

                <div class="p-5">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-brand-600 text-lg font-semibold text-white">
                            {{ $initials($admin->name) }}
                        </div>
                        <div class="min-w-0">
                            <p class="truncate text-base font-semibold text-slate-900">{{ $admin->name }}</p>
                            <p class="truncate text-sm text-slate-500">{{ '@'.($admin->username ?? $admin->email) }}</p>
                            <span class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Administrator
                            </span>
                        </div>
                    </div>

                    <div class="my-5 border-t border-slate-100"></div>

                    <dl class="space-y-3.5 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Username</dt>
                            <dd class="text-right font-semibold text-slate-900">{{ $admin->username ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Display name</dt>
                            <dd class="text-right font-semibold text-slate-900">{{ $admin->name }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Role</dt>
                            <dd class="text-right font-semibold text-slate-900">Administrator</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-slate-500">Member since</dt>
                            <dd class="text-right font-semibold text-slate-900">{{ $admin->created_at?->format('M d, Y') ?? 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <div class="xl:col-span-2">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-4">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold text-slate-900">Cashier Accounts</h3>
                            <p class="mt-0.5 text-xs text-slate-400">{{ $cashierTotal }} account{{ $cashierTotal === 1 ? '' : 's' }}</p>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <div class="relative">
                                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <input type="text" id="cashierSearch" placeholder="Search cashiers..." autocomplete="off"
                                    class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 sm:w-48"
                                    oninput="filterCashiers()"
                                    onkeydown="if(event.key === 'Enter'){ event.preventDefault(); filterCashiers(); }">
                            </div>
                            <x-button type="button" color="secondary" onclick="filterCashiers()">Search</x-button>
                            <x-button href="{{ route('accounts.create') }}" color="primary"
                                data-account-modal-url="{{ route('accounts.create') }}"
                                data-account-modal-title="Add Cashier"
                                data-account-modal-subtitle="Create a new account for store personnel">
                                <x-icon name="user-plus" class="h-4 w-4" /> Add Cashier
                            </x-button>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto no-scrollbar">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Name</th>
                                <th class="px-4 py-3 font-semibold">Username</th>
                                <th class="px-4 py-3 font-semibold">Status</th>
                                <th class="px-4 py-3 font-semibold">Date Created</th>
                                <th class="px-4 py-3 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($cashiers as $cashier)
                            <tr data-cashier-row data-search="{{ mb_strtolower($cashier->name.' '.$cashier->username) }}" class="transition-colors hover:bg-slate-50">
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-600">
                                            {{ $initials($cashier->name) }}
                                        </div>
                                        <span class="font-medium text-slate-900">{{ $cashier->name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-slate-600">{{ $cashier->username ?? '—' }}</td>
                                <td class="px-4 py-3.5">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <form action="{{ route('accounts.status', $cashier) }}" method="POST" class="inline-flex">
                                            @csrf
                                            <button type="submit" role="switch" aria-checked="{{ $cashier->is_active ? 'true' : 'false' }}"
                                                aria-label="{{ $cashier->is_active ? 'Disable' : 'Enable' }} {{ $cashier->name }}"
                                                title="{{ $cashier->is_active ? 'Disable account' : 'Enable account' }}"
                                                class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40 {{ $cashier->is_active ? 'bg-emerald-500' : 'bg-slate-300' }}">
                                                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform {{ $cashier->is_active ? 'translate-x-4' : 'translate-x-0.5' }}"></span>
                                            </button>
                                        </form>
                                        @if($cashier->is_active)
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                                                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                                Disabled
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-slate-600">{{ $cashier->created_at?->format('M d, Y') ?? 'N/A' }}</td>
                                <td class="px-4 py-3.5">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <x-button href="{{ route('accounts.edit', $cashier) }}" color="secondary" size="sm" class="whitespace-nowrap"
                                            data-account-modal-url="{{ route('accounts.edit', $cashier) }}"
                                            data-account-modal-title="Edit Cashier"
                                            data-account-modal-subtitle="Update account details for {{ $cashier->name }}">
                                            <x-icon name="edit" class="h-4 w-4" /> Edit
                                        </x-button>
                                        <x-button href="{{ route('accounts.password', $cashier) }}" color="dangerGhost" size="sm" class="whitespace-nowrap">
                                            <x-icon name="id-card" class="h-4 w-4" /> Reset Password
                                        </x-button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-5 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400">
                                            <x-icon name="users" class="h-4 w-4" />
                                        </div>
                                        <p class="text-base font-semibold text-slate-700">No cashier accounts yet</p>
                                        <p class="mt-1 text-sm text-slate-500">Add a cashier account to get started.</p>
                                        <x-button href="{{ route('accounts.create') }}" color="primary" class="mt-5"
                                            data-account-modal-url="{{ route('accounts.create') }}"
                                            data-account-modal-title="Add Cashier"
                                            data-account-modal-subtitle="Create a new account for store personnel">
                                            <x-icon name="user-plus" class="h-4 w-4" /> Add Cashier
                                        </x-button>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                            <tr id="noCashierMatches" class="hidden">
                                <td colspan="5" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-400">
                                            <x-icon name="search" class="h-3.5 w-3.5" />
                                        </div>
                                        <p class="text-sm font-semibold text-slate-700">No cashiers match your search</p>
                                        <p class="mt-1 text-xs text-slate-500">Try a different name or username.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="accountModal" class="fixed inset-0 z-[60] hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" data-account-modal-close></div>
    <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="accountModalTitle">
            <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
                <div class="min-w-0">
                    <h3 id="accountModalTitle" class="truncate text-base font-semibold text-slate-900">Account</h3>
                    <p id="accountModalSubtitle" class="mt-0.5 text-xs text-slate-500"></p>
                </div>
                <button type="button" data-account-modal-close class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/40" aria-label="Close">
                    <x-icon name="times" class="h-4 w-4" />
                </button>
            </div>
            <div id="accountModalBody" class="no-scrollbar max-h-[calc(100vh-8rem)] overflow-y-auto px-5 py-5">
                <div id="accountModalErrors" class="mb-5 hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <div class="flex items-center gap-2 font-medium">
                        <x-icon name="exclamation-circle" class="h-4 w-4" />
                        Please fix the following:
                    </div>
                    <ul id="accountModalErrorList" class="mt-2 list-inside list-disc space-y-1"></ul>
                </div>
                <div id="accountModalLoading" class="flex items-center justify-center gap-2 py-10 text-sm text-slate-500">
                    Loading...
                </div>
                <div id="accountModalContent" class="hidden"></div>
            </div>
        </div>
    </div>
</div>

<script>
    function filterCashiers() {
        const input = document.getElementById('cashierSearch');
        const rows = document.querySelectorAll('[data-cashier-row]');
        const noMatches = document.getElementById('noCashierMatches');

        if (!input) {
            return;
        }

        const term = input.value.trim().toLowerCase();
        let visible = 0;

        rows.forEach(function (row) {
            const matches = row.dataset.search.includes(term);
            row.classList.toggle('hidden', !matches);

            if (matches) {
                visible++;
            }
        });

        if (noMatches) {
            noMatches.classList.toggle('hidden', visible !== 0 || rows.length === 0);
        }
    }

    (function () {
        const modal = document.getElementById('accountModal');
        if (!modal) {
            return;
        }

        const content = document.getElementById('accountModalContent');
        const loading = document.getElementById('accountModalLoading');
        const errors = document.getElementById('accountModalErrors');
        const errorList = document.getElementById('accountModalErrorList');
        const title = document.getElementById('accountModalTitle');
        const subtitle = document.getElementById('accountModalSubtitle');
        const indexUrl = @json(route('accounts.index'));
        let activeTrigger = null;
        let loadToken = 0;

        function hideErrors() {
            errors.classList.add('hidden');
            errorList.innerHTML = '';
        }

        function showErrors(messages) {
            errorList.innerHTML = '';

            messages.forEach(function (message) {
                const item = document.createElement('li');
                item.textContent = message;
                errorList.appendChild(item);
            });

            errors.classList.remove('hidden');
            errors.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function setLoading(isLoading) {
            loading.classList.toggle('hidden', !isLoading);
            content.classList.toggle('hidden', isLoading);

            if (isLoading) {
                content.innerHTML = '';
            }
        }

        function openModal() {
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
        }

        function closeModal() {
            loadToken++;
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
            content.innerHTML = '';
            content.classList.add('hidden');
            loading.classList.add('hidden');
            hideErrors();
            title.textContent = 'Account';
            subtitle.textContent = '';

            if (activeTrigger) {
                activeTrigger.focus({ preventScroll: true });
                activeTrigger = null;
            }
        }

        function wireCancelLink(form) {
            form.querySelectorAll('a').forEach(function (link) {
                const href = link.getAttribute('href');

                if (!href) {
                    return;
                }

                if (new URL(href, window.location.origin).pathname === new URL(indexUrl, window.location.origin).pathname) {
                    link.setAttribute('href', '#');
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        closeModal();
                    });
                }
            });
        }

        function wireForm(form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                hideErrors();

                const submitButton = form.querySelector('button[type="submit"]');
                const submitMarkup = submitButton ? submitButton.innerHTML : '';

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Saving...';
                }

                function restoreSubmitButton() {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = submitMarkup;
                    }
                }

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        credentials: 'same-origin',
                        redirect: 'manual',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (response.type === 'opaqueredirect' || response.status === 0 || (response.status >= 300 && response.status < 400)) {
                        window.location.reload();
                        return;
                    }

                    if (response.status === 422) {
                        const data = await response.json();
                        const messages = Object.values(data.errors || {}).flat();
                        showErrors(messages.length ? messages : [data.message || 'Please check the form and try again.']);
                        restoreSubmitButton();
                        return;
                    }

                    if (!response.ok) {
                        throw new Error('Request failed with status ' + response.status);
                    }

                    window.location.reload();
                } catch (error) {
                    showErrors(['Something went wrong. Please try again.']);
                    restoreSubmitButton();
                }
            });
        }

        async function loadModal(trigger) {
            const token = ++loadToken;
            activeTrigger = trigger;
            title.textContent = trigger.dataset.accountModalTitle || 'Account';
            subtitle.textContent = trigger.dataset.accountModalSubtitle || '';
            hideErrors();
            setLoading(true);
            openModal();

            try {
                const response = await fetch(trigger.dataset.accountModalUrl, {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (!response.ok) {
                    throw new Error('Request failed with status ' + response.status);
                }

                const requestedPath = new URL(trigger.dataset.accountModalUrl, window.location.origin).pathname;
                const responsePath = new URL(response.url, window.location.origin).pathname;

                if (response.redirected && responsePath !== requestedPath) {
                    window.location.href = response.url;
                    return;
                }

                const html = await response.text();
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const form = doc.querySelector('main form') || doc.querySelector('form');

                if (!form) {
                    throw new Error('Form not found');
                }

                if (token !== loadToken || modal.classList.contains('hidden')) {
                    return;
                }

                content.innerHTML = '';
                content.appendChild(form);
                wireForm(form);
                wireCancelLink(form);
                setLoading(false);

                const firstField = form.querySelector('input:not([type="hidden"]), select, textarea');
                if (firstField) {
                    firstField.focus({ preventScroll: true });
                }
            } catch (error) {
                if (token !== loadToken || modal.classList.contains('hidden')) {
                    return;
                }

                setLoading(false);
                showErrors(['Unable to load this form. Please try again.']);
            }
        }

        document.querySelectorAll('[data-account-modal-url]').forEach(function (trigger) {
            trigger.addEventListener('click', function (event) {
                if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
                    return;
                }

                event.preventDefault();
                loadModal(trigger);
            });
        });

        modal.querySelectorAll('[data-account-modal-close]').forEach(function (element) {
            element.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    })();
</script>
@endsection
