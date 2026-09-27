
@props([
    'href' => null,
    'variant' => 'primary',   // primary | secondary | ghost | outline | light
    'size' => '',             // sm | lg
    'icon' => null,
    'iconAfter' => null,
    'type' => null,
    'disabled' => false,
    'block' => false,
    'newTab' => false,
])

@php
    $classes = 'btn btn-'.$variant;
    if ($size) $classes .= ' btn-'.$size;
    if ($block) $classes .= ' w-100';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes])->merge(['rel' => $newTab ? 'noopener' : null]) }}
       @if ($newTab) target="_blank" @endif>
        @if ($icon)<i class="ti {{ $icon }} me-1"></i>@endif{{ $slot }}
        @if ($iconAfter)<i class="ti {{ $iconAfter }} ms-1"></i>@endif
    </a>
@else
    <button type="{{ $type ?: 'button' }}" {{ $attributes->class([$classes])->merge(['disabled' => $disabled]) }}>
        @if ($icon)<i class="ti {{ $icon }} me-1"></i>@endif{{ $slot }}
        @if ($iconAfter)<i class="ti {{ $iconAfter }} ms-1"></i>@endif
    </button>
@endif
