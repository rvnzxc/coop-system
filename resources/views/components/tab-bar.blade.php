@props([
    'tabs' => [],
    'active' => null,
])

<div {{ $attributes->merge(['class' => 'mb-6 flex gap-2 border-b border-slate-200']) }}>
    @foreach($tabs as $tab)
        <a
            href="{{ $tab['href'] }}"
            data-tab="{{ $tab['key'] }}"
            role="tab"
            aria-selected="{{ $active === $tab['key'] ? 'true' : 'false' }}"
            class="border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors {{ $active === $tab['key'] ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}"
        >
            {{ $tab['label'] }}
            @if (! empty($tab['badge']))
                <span class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $active === $tab['key'] ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-slate-600' }}">{{ $tab['badge'] }}</span>
            @endif
        </a>
    @endforeach
</div>
