@props([
    'title' => null,
    'url' => null,
    'excerpt' => null,
    'image' => null,
    'imageAlt' => null,
    'icon' => null,
    'meta' => null,
    'cta' => 'Learn more',
    'featured' => false,
    'newTab' => false,
])

<{{ $url ? 'a' : 'div' }}
    @if ($url) href="{{ $url }}" @endif
    @if ($newTab) target="_blank" rel="noopener" @endif
    {{ $attributes->class(['card h-100 text-decoration-none', 'card-link' => (bool) $url]) }}
>
    @if ($image)
        <img src="{{ $image }}" alt="{{ $imageAlt ?? $title }}" class="card-img-top lindu-thumb" loading="lazy">
    @endif

    <div class="card-body d-flex flex-column {{ $featured ? 'p-4' : '' }}">
        @if ($icon)
            <div class="fs-1 mb-2" style="color:var(--tblr-primary)">{{ $icon }}</div>
        @endif

        @if ($meta)
            <span class="badge bg-blue-lt align-self-start mb-2">{{ $meta }}</span>
        @endif

        @if ($title)
            <h2 class="card-title {{ $featured ? 'h3' : 'h4' }} mb-2">{{ $title }}</h2>
        @endif

        @if ($excerpt)
            <p class="text-secondary {{ $featured ? '' : 'flex-grow-1' }}">
                {{ \Illuminate\Support\Str::limit(strip_tags((string) $excerpt), $featured ? 220 : 130) }}
            </p>
        @endif

        @if ($url)
            <span class="text-primary fw-semibold mt-auto">
                {{ $cta }} <i class="ti ti-arrow-right ms-1"></i>
            </span>
        @endif
    </div>
</{{ $url ? 'a' : 'div' }}>
