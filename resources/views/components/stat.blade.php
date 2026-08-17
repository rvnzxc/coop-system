@props([
    'label' => null,
    'value' => null,
    'icon' => null,
    'tone' => 'green',
])

@php
    $tones = [
        'green' => 'bg-brand-50 text-brand-600',
        'blue' => 'bg-sky-50 text-sky-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'red' => 'bg-red-50 text-red-600',
        'violet' => 'bg-violet-50 text-violet-600',
        'gray' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-slate-200 shadow-sm p-5']) }}>
    <div class="flex items-center gap-4">
        @if ($icon)
            <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-base {{ $tones[$tone] ?? $tones['green'] }}"
            >
                <i class="{{ $icon }}"></i>
            </div>
        @endif
        <div class="min-w-0">
            <div class="truncate text-2xl font-bold text-slate-900">{{ $value }}</div>
            <div class="mt-0.5 text-sm text-slate-500">{{ $label }}</div>
        </div>
    </div>
</div>
