@props([
    'title' => null,
    'eyebrow' => null,
    'description' => null,
    'href' => null,
    'image' => null,
    'imageAlt' => '',
    'icon' => null,
    'badge' => null,
    'align' => 'center', // center | left
    'alt' => false, // alternate background
    'tight' => false,
])

@php
    $classes = ['lindu-section'];
    if ($alt) $classes[] = 'lindu-section--alt';
    if ($tight) $classes[] = 'lindu-section--tight';
@endphp

<section {{ $attributes->class($classes) }}>
    <div class="container-xl">
        <x-site.section-header
            :title="$title"
            :eyebrow="$eyebrow"
            :description="$description"
            :align="$align"
        />

        @if (trim($slot))
            {{ $slot }}
        @elseif ($href)
            <div class="text-center mt-4">
                <a href="{{ $href }}" class="btn btn-outline-primary">
                    {{ $badge ?: 'Learn more' }} <i class="ti ti-arrow-right ms-1"></i>
                </a>
            </div>
        @endif
    </div>
</section>
