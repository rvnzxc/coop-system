@extends('layouts.app')

@section('title', 'Members')

@section('content')
<div class="mx-auto max-w-7xl">
    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            <i class="fa fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    {{-- Page header --}}
    <div class="mb-6 flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
        <div class="min-w-0">
            <h2 class="text-xl font-bold text-slate-900">Members Management</h2>
            <p class="mt-0.5 text-sm text-slate-500">Track member profiles, purchases, and loyalty</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <form action="{{ route('members.index') }}" method="GET" class="flex gap-2">
                <div class="relative">
                    <i class="fa fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                    <input type="text" name="search" placeholder="Search members..." value="{{ $search ?? '' }}" class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 sm:w-64">
                </div>
                <x-button type="submit" color="secondary" size="sm">Search</x-button>
            </form>
            <x-button href="{{ route('members.create') }}" color="primary">
                <i class="fa fa-user-plus"></i> Add Member
            </x-button>
        </div>
    </div>

    {{-- Summary stats --}}
    @php
        $totalMembers = $members->count();
        $activeCount = $members->where('is_active', true)->count();
        $totalPurchases = $members->sum('total_purchases');
    @endphp
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-base text-brand-600">
                <i class="fa fa-users"></i>
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">{{ $totalMembers }}</div>
                <div class="text-xs text-slate-500">Total Members</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-base text-emerald-600">
                <i class="fa fa-check-circle"></i>
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">{{ $activeCount }}</div>
                <div class="text-xs text-slate-500">Active Members</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-base text-sky-600">
                <i class="fa fa-money"></i>
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">₱{{ number_format($totalPurchases, 2) }}</div>
                <div class="text-xs text-slate-500">Total Purchases</div>
            </div>
        </div>
    </div>

    {{-- Members table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Member Records</h3>
            @if($search)
                <a href="{{ route('members.index') }}" class="text-xs font-medium text-slate-500 transition-colors hover:text-brand-600">
                    <i class="fa fa-times"></i> Clear search
                </a>
            @endif
        </div>
        <div class="max-h-[calc(100vh-24rem)] overflow-auto">
            <table class="w-full text-left text-sm">
                <thead class="sticky top-0 z-10 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5 font-semibold">Member</th>
                        <th class="px-5 py-3.5 font-semibold">Contact</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Purchases</th>
                        <th class="px-5 py-3.5 font-semibold">Last Purchase</th>
                        <th class="px-5 py-3.5 font-semibold">Status</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($members as $member)
                    <tr class="transition-colors hover:bg-slate-50">
                        <td class="px-5 py-3.5">
                            <div class="font-medium text-slate-900">{{ $member->first_name }} {{ $member->last_name }}</div>
                            <div class="text-xs text-slate-400">#{{ $member->member_number }}</div>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="text-slate-600">{{ $member->email ?? 'N/A' }}</div>
                            <div class="text-xs text-slate-400">{{ $member->phone ?? 'No phone' }}</div>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-right">
                            <div class="font-semibold text-slate-700">₱{{ number_format($member->total_purchases, 2) }}</div>
                            <div class="text-xs text-slate-400">{{ $member->purchase_count }} transaction{{ $member->purchase_count != 1 ? 's' : '' }}</div>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-slate-500">{{ $member->last_purchase_date ? $member->last_purchase_date->format('M d, Y') : 'Never' }}</td>
                        <td class="px-5 py-3.5">
                            @if($member->is_active)
                                <x-badge color="green"><i class="fa fa-circle text-[6px]"></i> Active</x-badge>
                            @else
                                <x-badge color="red"><i class="fa fa-circle text-[6px]"></i> Inactive</x-badge>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5">
                            <div class="flex items-center justify-end gap-1.5">
                                <x-button href="{{ route('members.edit', $member->id) }}" color="secondary" size="sm" title="Edit Member">
                                    <i class="fa fa-edit"></i>
                                </x-button>
                                <x-button href="{{ route('members.analytics', $member->id) }}" color="secondary" size="sm" title="View Analytics">
                                    <i class="fa fa-bar-chart"></i>
                                </x-button>
                                <x-button href="{{ route('members.card', $member->id) }}" color="secondary" size="sm" title="View ID Card">
                                    <i class="fa fa-id-card"></i>
                                </x-button>
                                <form action="{{ route('members.destroy', $member->id) }}" method="POST" class="delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <x-button type="submit" color="dangerGhost" size="sm" title="Delete Member" onclick="return confirm('Are you sure you want to delete this member?')">
                                        <i class="fa fa-trash"></i>
                                    </x-button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center">
                                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400">
                                    <i class="fa fa-users"></i>
                                </div>
                                <p class="text-base font-semibold text-slate-700">{{ $search ? 'No members match your search' : 'No members found' }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $search ? 'Try a different search term.' : 'Start by adding your first member.' }}</p>
                                <x-button href="{{ route('members.create') }}" color="primary" class="mt-5">
                                    <i class="fa fa-user-plus"></i> Add Your First Member
                                </x-button>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
