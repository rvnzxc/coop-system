@extends('layouts.app')

@section('title', 'Members')

@section('content')
<div class="mx-auto max-w-7xl">
    {{-- Page header --}}
    <div class="mb-6 flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
        <div class="min-w-0">
            <h2 class="text-xl font-bold text-slate-900">Members Management</h2>
            <p class="mt-0.5 text-sm text-slate-500">Track member profiles, purchases, and loyalty</p>
        </div>
        <div class="table-toolbar flex flex-col gap-3 sm:flex-row sm:items-center">
            <form action="{{ route('members.index') }}" method="GET" class="flex min-w-0 gap-2">
                @if($tab === 'archived')
                    <input type="hidden" name="tab" value="archived">
                @endif
                <div class="relative min-w-0 flex-1">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-400" />
                    <input type="text" name="search" placeholder="Search members..." value="{{ $search ?? '' }}" class="table-search-input w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 sm:w-64">
                </div>
                <x-button type="submit" color="secondary" size="sm" class="table-toolbar-btn">Search</x-button>
            </form>
            @if($tab === 'active')
                <x-button href="{{ route('members.create') }}" color="primary" class="table-toolbar-btn justify-center">
                    <x-icon name="user-plus" class="h-3.5 w-3.5" /> Add Member
                </x-button>
            @endif
        </div>
    </div>

    <x-tab-bar
        class="overflow-x-auto whitespace-nowrap"
        :tabs="[
            ['key' => 'active', 'label' => 'Active Members', 'href' => route('members.index')],
            ['key' => 'archived', 'label' => 'Archived', 'href' => route('members.index', ['tab' => 'archived']), 'badge' => $archivedCount > 0 ? $archivedCount : null],
        ]"
        :active="$tab"
    />

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if($tab === 'active')
        {{-- Summary stats --}}
        @php
            $totalMembers = $members->count();
            $activeCount = $members->where('is_active', true)->count();
            $totalPurchases = $members->sum('total_purchases');
        @endphp
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-base text-brand-600">
                    <x-icon name="users" class="h-4 w-4" />
                </div>
                <div class="min-w-0">
                    <div class="truncate text-xl font-bold text-slate-900">{{ $totalMembers }}</div>
                    <div class="text-xs text-slate-500">Total Members</div>
                </div>
            </div>
            <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-base text-emerald-600">
                    <x-icon name="check-circle" class="h-4 w-4" />
                </div>
                <div class="min-w-0">
                    <div class="truncate text-xl font-bold text-slate-900">{{ $activeCount }}</div>
                    <div class="text-xs text-slate-500">Active Members</div>
                </div>
            </div>
            <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-base text-sky-600">
                    <x-icon name="money" class="h-4 w-4" />
                </div>
                <div class="min-w-0">
                    <div class="truncate text-xl font-bold text-slate-900">&#8369;{{ number_format($totalPurchases, 2) }}</div>
                    <div class="text-xs text-slate-500">Total Purchases</div>
                </div>
            </div>
        </div>
    @endif

    {{-- Members table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">{{ $tab === 'archived' ? 'Archived Members' : 'Member Records' }}</h3>
            @if($search)
                <a href="{{ route('members.index', $tab === 'archived' ? ['tab' => 'archived'] : []) }}" class="text-xs font-medium text-slate-500 transition-colors hover:text-brand-600">
                    <x-icon name="times" class="h-3 w-3" /> Clear search
                </a>
            @endif
        </div>
        <div class="app-table-frame min-w-0 max-w-full" data-table-frame>
            <div class="app-table-wrap max-h-[calc(100vh-24rem)] overflow-auto" data-table-scroll>
                <table class="app-table app-table--members">
                <thead class="sticky top-0 z-10 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="col-sticky-left px-5 py-3.5 font-semibold">Member</th>
                        <th class="tablet-hide px-5 py-3.5 font-semibold">Contact</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Purchases</th>
                        <th class="tablet-hide px-5 py-3.5 font-semibold">Last Purchase</th>
                        @if($tab === 'archived')
                            <th class="px-5 py-3.5 font-semibold">Archived On</th>
                        @else
                            <th class="px-5 py-3.5 font-semibold">Status</th>
                        @endif
                        <th class="col-sticky-right px-5 py-3.5 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($members as $member)
                    <tr class="transition-colors hover:bg-slate-50">
                        <td class="col-sticky-left cell-wrap px-5 py-3.5">
                            <div class="font-medium text-slate-900">{{ $member->first_name }} {{ $member->last_name }}</div>
                            <div class="text-xs text-slate-400">{{ $member->member_number }}</div>
                        </td>
                        <td class="tablet-hide px-5 py-3.5">
                            <div class="text-slate-600">{{ $member->email ?? 'N/A' }}</div>
                            <div class="text-xs text-slate-400">{{ $member->phone ?? 'No phone' }}</div>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-right">
                            <div class="font-semibold text-slate-700">&#8369;{{ number_format($member->total_purchases, 2) }}</div>
                            <div class="text-xs text-slate-400">{{ $member->purchase_count }} transaction{{ $member->purchase_count != 1 ? 's' : '' }}</div>
                        </td>
                        <td class="tablet-hide whitespace-nowrap px-5 py-3.5 text-slate-500">{{ $member->last_purchase_date ? $member->last_purchase_date->format('M d, Y') : 'Never' }}</td>
                        @if($tab === 'archived')
                            <td class="whitespace-nowrap px-5 py-3.5 text-slate-500">{{ $member->archived_at?->format('M d, Y') }}</td>
                        @else
                            <td class="px-5 py-3.5">
                                @if($member->is_active)
                                    <x-badge color="green"><x-icon name="circle" class="h-1.5 w-1.5" /> Active</x-badge>
                                @else
                                    <x-badge color="red"><x-icon name="circle" class="h-1.5 w-1.5" /> Inactive</x-badge>
                                @endif
                            </td>
                        @endif
                        <td class="col-sticky-right whitespace-nowrap px-5 py-3.5">
                            <div class="table-actions">
                                @if($tab === 'archived')
                                    <x-button type="button" color="secondary" size="sm" class="!h-8 !w-8 !px-0 !border-emerald-200 !text-emerald-600 hover:!bg-emerald-50" title="Restore member" aria-label="Restore member" data-restore-trigger data-restore-url="{{ route('members.restore', $member->id) }}" data-member-name="{{ $member->first_name }} {{ $member->last_name }}" data-member-number="{{ $member->member_number }}">
                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                            <path d="M4 9h10a6 6 0 0 1 0 12h-2" />
                                            <path d="M4 9l5-5M4 9l5 5" />
                                        </svg>
                                    </x-button>
                                    <x-button href="{{ route('members.card', $member->id) }}" color="secondary" size="sm" class="!h-8 !w-8 !px-0" title="View ID Card" aria-label="View ID card for {{ $member->first_name }} {{ $member->last_name }}">
                                        <x-icon name="id-card" class="h-3 w-3" />
                                    </x-button>
                                @else
                                    <x-button href="{{ route('members.edit', $member->id) }}" color="secondary" size="sm" class="!h-8 !w-8 !px-0" title="Edit Member" aria-label="Edit {{ $member->first_name }} {{ $member->last_name }}">
                                        <x-icon name="edit" class="h-3 w-3" />
                                    </x-button>
                                    <x-button href="{{ route('members.analytics', $member->id) }}" color="secondary" size="sm" class="!h-8 !w-8 !px-0" title="View Analytics" aria-label="View analytics for {{ $member->first_name }} {{ $member->last_name }}">
                                        <x-icon name="bar-chart" class="h-3 w-3" />
                                    </x-button>
                                    <x-button href="{{ route('members.card', $member->id) }}" color="secondary" size="sm" class="!h-8 !w-8 !px-0" title="View ID Card" aria-label="View ID card for {{ $member->first_name }} {{ $member->last_name }}">
                                        <x-icon name="id-card" class="h-3 w-3" />
                                    </x-button>
                                    <x-button type="button" color="secondary" size="sm" class="!h-8 !w-8 !px-0 !border-amber-200 !text-amber-600 hover:!bg-amber-50" title="Archive member" aria-label="Archive member" data-archive-trigger data-archive-url="{{ route('members.archive', $member->id) }}" data-member-name="{{ $member->first_name }} {{ $member->last_name }}" data-member-number="{{ $member->member_number }}">
                                        <x-icon name="archive" class="h-3 w-3" />
                                    </x-button>
                                    <form action="{{ route('members.destroy', $member->id) }}" method="POST" class="delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" color="dangerGhost" size="sm" class="!h-8 !w-8 !px-0" title="Delete Member" aria-label="Delete {{ $member->first_name }} {{ $member->last_name }}" onclick="return confirmDestructive(event, 'Are you sure you want to delete this member?', 'Delete Member')">
                                            <x-icon name="trash" class="h-3 w-3" />
                                        </x-button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="cell-empty px-5 py-16 text-center">
                            <div class="flex flex-col items-center">
                                @if($tab === 'archived')
                                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400">
                                        <x-icon name="archive" class="h-6 w-6" />
                                    </div>
                                    <p class="text-base font-semibold text-slate-700">No archived members</p>
                                    <p class="mt-1 text-sm text-slate-500">Members you archive will appear here.</p>
                                @else
                                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400">
                                        <x-icon name="users" class="h-6 w-6" />
                                    </div>
                                    <p class="text-base font-semibold text-slate-700">{{ $search ? 'No members match your search' : 'No members found' }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $search ? 'Try a different search term.' : 'Start by adding your first member.' }}</p>
                                    <x-button href="{{ route('members.create') }}" color="primary" class="mt-5">
                                        <x-icon name="user-plus" class="h-3.5 w-3.5" /> Add Your First Member
                                    </x-button>
                                @endif
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

<div id="archiveModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="archiveModalTitle" aria-describedby="archiveModalMessage archiveModalHelper" tabindex="-1">
    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl" role="document">
        <button type="button" id="archiveModalClose" class="absolute right-5 top-4 text-2xl leading-none text-slate-400 transition-colors hover:text-slate-600" aria-label="Close dialog">
            <x-icon name="times" class="h-4 w-4" />
        </button>
        <h3 id="archiveModalTitle" class="text-lg font-semibold text-slate-900">Archive Member</h3>
        <p id="archiveModalMessage" class="mt-3 text-sm text-slate-700">Are you sure you want to archive this member?</p>
        <p id="archiveMemberDetails" class="mt-1 text-sm font-medium text-slate-900"></p>
        <p id="archiveModalHelper" class="mt-3 text-xs leading-relaxed text-slate-500">Archived members won't appear in the active list or POS, but their purchase history is kept. You can restore them later.</p>
        <form id="archiveForm" method="POST" action="" class="mt-6">
            @csrf
            @method('PATCH')
            <div class="flex justify-end gap-3">
                <x-button type="button" id="archiveCancel" color="secondary">Cancel</x-button>
                <x-button type="submit" id="archiveConfirm" color="secondary" class="!border-amber-300 !bg-amber-500 !text-white hover:!bg-amber-600">Yes, Archive</x-button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('archiveModal');
    const archiveForm = document.getElementById('archiveForm');
    const confirmButton = document.getElementById('archiveConfirm');
    const cancelButton = document.getElementById('archiveCancel');
    const closeButton = document.getElementById('archiveModalClose');
    const memberDetails = document.getElementById('archiveMemberDetails');
    let lastFocusedElement = null;
    let submitting = false;

    function focusableElements() {
        return Array.from(modal.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), [tabindex]:not([tabindex="-1"])'))
            .filter(function (element) { return element.offsetParent !== null; });
    }

    function openArchiveModal(trigger) {
        lastFocusedElement = document.activeElement;
        submitting = false;
        confirmButton.disabled = false;
        confirmButton.removeAttribute('aria-busy');
        confirmButton.classList.remove('opacity-70');
        confirmButton.textContent = 'Yes, Archive';
        archiveForm.action = trigger.dataset.archiveUrl;
        memberDetails.textContent = trigger.dataset.memberName + ' (' + trigger.dataset.memberNumber + ')';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        cancelButton.focus();
    }

    function closeArchiveModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (lastFocusedElement) lastFocusedElement.focus();
    }

    document.querySelectorAll('[data-archive-trigger]').forEach(function (trigger) {
        trigger.addEventListener('click', function () { openArchiveModal(trigger); });
    });

    cancelButton.addEventListener('click', closeArchiveModal);
    closeButton.addEventListener('click', closeArchiveModal);
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeArchiveModal();
    });

    document.addEventListener('keydown', function (event) {
        if (modal.classList.contains('hidden')) return;

        if (event.key === 'Escape') {
            event.preventDefault();
            closeArchiveModal();
            return;
        }

        if (event.key !== 'Tab') return;

        const elements = focusableElements();
        if (!elements.length) return;

        const first = elements[0];
        const last = elements[elements.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    archiveForm.addEventListener('submit', function (event) {
        event.preventDefault();
        if (submitting) return;

        submitting = true;
        confirmButton.disabled = true;
        confirmButton.setAttribute('aria-busy', 'true');
        confirmButton.classList.add('opacity-70');
        confirmButton.innerHTML = '<svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg> Archiving...';
        archiveForm.submit();
    });
})();
</script>

<div id="restoreModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="restoreModalTitle" aria-describedby="restoreModalMessage restoreModalHelper" tabindex="-1">
    <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl" role="document">
        <button type="button" id="restoreModalClose" class="absolute right-5 top-4 text-2xl leading-none text-slate-400 transition-colors hover:text-slate-600" aria-label="Close dialog">
            <x-icon name="times" class="h-4 w-4" />
        </button>
        <h3 id="restoreModalTitle" class="text-lg font-semibold text-slate-900">Restore Member</h3>
        <p id="restoreModalMessage" class="mt-3 text-sm text-slate-700">Are you sure you want to restore this member?</p>
        <p id="restoreMemberDetails" class="mt-1 text-sm font-medium text-slate-900"></p>
        <p id="restoreModalHelper" class="mt-3 text-xs leading-relaxed text-slate-500">They will appear in the active list and can be used in POS again.</p>
        <form id="restoreForm" method="POST" action="" class="mt-6">
            @csrf
            @method('PATCH')
            <div class="flex justify-end gap-3">
                <x-button type="button" id="restoreCancel" color="secondary">Cancel</x-button>
                <x-button type="submit" id="restoreConfirm" color="secondary" class="!border-emerald-300 !bg-emerald-600 !text-white hover:!bg-emerald-700">Yes, Restore</x-button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('restoreModal');
    const restoreForm = document.getElementById('restoreForm');
    const confirmButton = document.getElementById('restoreConfirm');
    const cancelButton = document.getElementById('restoreCancel');
    const closeButton = document.getElementById('restoreModalClose');
    const memberDetails = document.getElementById('restoreMemberDetails');
    let lastFocusedElement = null;
    let submitting = false;

    function focusableElements() {
        return Array.from(modal.querySelectorAll('button:not([disabled]), [href], input:not([disabled]), [tabindex]:not([tabindex="-1"])'))
            .filter(function (element) { return element.offsetParent !== null; });
    }

    function openRestoreModal(trigger) {
        lastFocusedElement = document.activeElement;
        submitting = false;
        confirmButton.disabled = false;
        confirmButton.removeAttribute('aria-busy');
        confirmButton.classList.remove('opacity-70');
        confirmButton.textContent = 'Yes, Restore';
        restoreForm.action = trigger.dataset.restoreUrl;
        memberDetails.textContent = trigger.dataset.memberName + ' (' + trigger.dataset.memberNumber + ')';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        cancelButton.focus();
    }

    function closeRestoreModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        if (lastFocusedElement) lastFocusedElement.focus();
    }

    document.querySelectorAll('[data-restore-trigger]').forEach(function (trigger) {
        trigger.addEventListener('click', function () { openRestoreModal(trigger); });
    });

    cancelButton.addEventListener('click', closeRestoreModal);
    closeButton.addEventListener('click', closeRestoreModal);
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeRestoreModal();
    });

    document.addEventListener('keydown', function (event) {
        if (modal.classList.contains('hidden')) return;

        if (event.key === 'Escape') {
            event.preventDefault();
            closeRestoreModal();
            return;
        }

        if (event.key !== 'Tab') return;

        const elements = focusableElements();
        if (!elements.length) return;

        const first = elements[0];
        const last = elements[elements.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    restoreForm.addEventListener('submit', function (event) {
        event.preventDefault();
        if (submitting) return;

        submitting = true;
        confirmButton.disabled = true;
        confirmButton.setAttribute('aria-busy', 'true');
        confirmButton.classList.add('opacity-70');
        confirmButton.innerHTML = '<svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg> Restoring...';
        restoreForm.submit();
    });
})();
</script>
@endsection
