@extends('layouts.app')

@section('title', 'Activity Log')

@section('content')
@php
    $inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30';
    $labelClass = 'mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400';
@endphp

<div class="mx-auto max-w-7xl">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900">Transactions</h2>
        <p class="mt-0.5 text-sm text-slate-500">Review every sale, filter by cashier or date, and export the results</p>
    </div>

    @include('transactions._tabs')

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('transactions.activity') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="sm:w-64">
                <label for="activityUser" class="{{ $labelClass }}">User</label>
                <select id="activityUser" name="user_id" class="{{ $inputClass }}">
                    <option value="">All users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((string) $userId === (string) $user->id)>{{ $user->name }}@if($user->username) ({{ $user->username }})@endif</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:w-56">
                <label for="activityAction" class="{{ $labelClass }}">Action</label>
                <select id="activityAction" name="action" class="{{ $inputClass }}">
                    <option value="">All actions</option>
                    @foreach($actions as $value => $label)
                        <option value="{{ $value }}" @selected($action === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if($userId || $action)
                    <x-button href="{{ route('transactions.activity') }}" color="ghost" size="sm">Reset</x-button>
                @endif
                <x-button type="submit" color="primary" size="sm">
                    <x-icon name="search" class="h-3 w-3" /> Filter
                </x-button>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Activity Log</h3>
            <p class="mt-0.5 text-xs text-slate-500">{{ number_format($logs->total()) }} entr{{ $logs->total() === 1 ? 'y' : 'ies' }}. Read-only audit trail of account and sales activity.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5 font-semibold">Date / Time</th>
                        <th class="px-5 py-3.5 font-semibold">User</th>
                        <th class="px-5 py-3.5 font-semibold">Action</th>
                        <th class="px-5 py-3.5 font-semibold">Target</th>
                        <th class="px-5 py-3.5 font-semibold">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr class="transition-colors hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-3.5 text-slate-600">{{ $log->created_at?->format('M d, Y g:i A') }}</td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                <div class="font-medium text-slate-900">{{ $log->user?->name ?: 'System / deleted user' }}</div>
                                @if($log->user?->username)
                                    <div class="text-xs text-slate-400">{{ '@'.$log->user->username }}</div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5">
                                @php
                                    $badgeColor = match (true) {
                                        $log->action === \App\Models\AuditLog::ACTION_SALE_COMPLETED => 'green',
                                        $log->action === \App\Models\AuditLog::ACTION_SALE_VOIDED => 'red',
                                        str_starts_with($log->action, 'account_') => 'blue',
                                        default => 'gray',
                                    };
                                @endphp
                                <x-badge :color="$badgeColor">{{ $log->action_label }}</x-badge>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-slate-600">
                                @if($log->target_type)
                                    {{ $log->target_type }}@if($log->target_id) #{{ $log->target_id }}@endif
                                @else
                                    &mdash;
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500">
                                @if($log->details)
                                    {{ collect($log->details)->map(fn ($value, $key) => $key.': '.(is_array($value) ? json_encode($value) : (is_bool($value) ? ($value ? 'yes' : 'no') : $value)))->implode(' · ') }}
                                @else
                                    &mdash;
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <x-icon name="clock-o" class="h-6 w-6" />
                                    </div>
                                    <p class="text-base font-semibold text-slate-700">No activity found</p>
                                    <p class="mt-1 text-sm text-slate-500">Login, sale and account events will appear here.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
