@props([
    'href' => null,
    'type' => 'button',
    'color' => 'primary',
    'size' => 'md',
])

@php
    $colors = [
        'primary' => 'bg-brand-600 text-white hover:bg-brand-700 focus-visible:outline-brand-600 shadow-sm',
        'secondary' => 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 shadow-sm',
        'ghost' => 'text-slate-600 hover:bg-slate-100',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus-visible:outline-red-600 shadow-sm',
        'dangerGhost' => 'bg-white text-red-600 border border-red-200 hover:bg-red-50',
        'dark' => 'bg-slate-900 text-white hover:bg-slate-800 focus-visible:outline-slate-900 shadow-sm',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-sm',
    ];

    $base =
        'inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition-colors ' .
        'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 ' .
        'disabled:opacity-50 disabled:cursor-not-allowed';
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        {{ $attributes->merge([
            'class' => trim($base . ' ' . ($colors[$color] ?? $colors['primary']) . ' ' . ($sizes[$size] ?? $sizes['md'])),
        ]) }}
    >
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $type }}"
        {{ $attributes->merge([
            'class' => trim($base . ' ' . ($colors[$color] ?? $colors['primary']) . ' ' . ($sizes[$size] ?? $sizes['md'])),
        ]) }}
    >
        {{ $slot }}
    </button>
@endif
