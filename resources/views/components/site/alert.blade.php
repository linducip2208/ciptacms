@props([
    'variant' => 'info',   // success | danger | warning | info
    'icon' => null,
    'dismissible' => false,
])

@php
    $icons = [
        'success' => 'ti ti-check-circle',
        'danger' => 'ti ti-alert-circle',
        'warning' => 'ti ti-alert-triangle',
        'info' => 'ti ti-info-circle',
    ];

    $iconClass = $icon ?: ($icons[$variant] ?? $icons['info']);
@endphp

<div {{ $attributes->class(['alert', 'alert-'.$variant, 'd-flex', 'align-items-start', 'mb-0']) }}
     @if ($variant === 'danger' || $variant === 'warning') role="alert" @else role="status" @endif>
    <i class="{{ $iconClass }} me-2 flex-shrink-0"></i>
    <div class="flex-grow-1">{{ $slot }}</div>
</div>
