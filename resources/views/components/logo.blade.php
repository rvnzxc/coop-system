@props([
    'size' => 'md',
    'showText' => true,
    'textSize' => null,
    'link' => null,
    'class' => '',
])

@php
    $sizes = [
        'xs' => ['img' => 'h-6 w-6', 'text' => 'text-xs'],
        'sm' => ['img' => 'h-8 w-8', 'text' => 'text-sm'],
        'md' => ['img' => 'h-9 w-9', 'text' => 'text-sm'],
        'lg' => ['img' => 'h-10 w-10', 'text' => 'text-base'],
        'xl' => ['img' => 'h-12 w-12', 'text' => 'text-lg'],
        '2xl' => ['img' => 'h-16 w-16', 'text' => 'text-xl'],
        'login' => ['img' => 'h-[120px] w-auto', 'text' => 'text-base'],
    ];
    
    $sizeConfig = $sizes[$size] ?? $sizes['md'];
    $imgClass = $sizeConfig['img'];
    $textClass = $textSize ?? $sizeConfig['text'];
    $logoSrc = asset('images/logo.nobg.png');
    if (!file_exists(public_path('images/logo.nobg.png'))) {
        $logoSrc = asset('images/logo.png');
    }
@endphp

@if($link)
    <a href="{{ $link }}" class="flex items-center gap-3 {{ $class }}">
        <img src="{{ $logoSrc }}" alt="CCFMPC logo" class="{{ $imgClass }} object-contain" loading="eager" width="{{ $size === 'login' ? '120' : null }}" height="{{ $size === 'login' ? '120' : null }}" />
        @if($showText)
            <div class="min-w-0 leading-tight">
                <p class="truncate {{ $textClass }} font-semibold text-slate-900">CCFMPC</p>
                <p class="truncate text-xs text-slate-500">Cooperative Store</p>
            </div>
        @endif
    </a>
@else
    <div class="flex items-center gap-3 {{ $class }}">
        <img src="{{ $logoSrc }}" alt="CCFMPC logo" class="{{ $imgClass }} object-contain" loading="eager" />
        @if($showText)
            <div class="min-w-0 leading-tight">
                <p class="truncate {{ $textClass }} font-semibold text-slate-900">CCFMPC</p>
                <p class="truncate text-xs text-slate-500">Cooperative Store</p>
            </div>
        @endif
    </div>
@endif
