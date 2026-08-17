@props([
    'color' => 'gray',
])

@php
    $colors = [
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'red' => 'bg-red-50 text-red-700 ring-red-600/20',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'blue' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
        'teal' => 'bg-teal-50 text-teal-700 ring-teal-600/20',
        'gray' => 'bg-slate-100 text-slate-600 ring-slate-500/20',
    ];
@endphp

<span
    {{ $attributes->merge([
        'class' =>
            'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ' .
            ($colors[$color] ?? $colors['gray']),
    ]) }}
>
    {{ $slot }}
</span>
