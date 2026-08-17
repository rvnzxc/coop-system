@props([
    'title' => null,
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-slate-200 shadow-sm']) }}>
    @if ($title || $description)
        <div class="px-6 py-4 border-b border-slate-100">
            @if ($title)
                <h3 class="text-base font-semibold text-slate-900">{{ $title }}</h3>
            @endif
            @if ($description)
                <p class="mt-0.5 text-sm text-slate-500">{{ $description }}</p>
            @endif
            {{ $header ?? '' }}
        </div>
    @endif
    <div class="p-6">{{ $slot }}</div>
</div>
