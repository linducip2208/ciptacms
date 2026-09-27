@props([
    'title' => null,
    'eyebrow' => null,
    'description' => null,
    'align' => 'center', // center | left
    'tag' => 'h2',
])

@php
    $centered = $align === 'center';
@endphp

<div {{ $attributes->class(['lindu-section-head', 'lindu-section-head--left' => ! $centered]) }}>
    @if ($eyebrow)
        <span class="lindu-eyebrow">{{ $eyebrow }}</span>
    @endif

    @if ($title)
        <{{ $tag }} class="h1 mb-2">{{ $title }}</{{ $tag }}>
    @endif

    @if ($description)
        <p class="text-secondary mb-0 {{ $centered ? 'mx-auto' : '' }}" style="max-width: 40rem">{{ $description }}</p>
    @endif

    @if (trim($slot))
        <div class="mt-3">{{ $slot }}</div>
    @endif
</div>
