@props([
    'items' => [],
    'title' => 'Trusted by',
    'description' => null,
    'alt' => true,
])

<x-site.section :title="$title" :description="$description" :alt="$alt" :tight="true">
    @if (filled($items))
        <div class="row g-4 align-items-center">
            @foreach ($items as $item)
                <div class="col-6 col-md-4 col-lg-3 text-center">
                    @if ($item['logo'] ?? null)
                        <a href="{{ $item['url'] ?: '#' }}"
                           @if ($item['url'] ?? null) target="_blank" rel="noopener" @endif
                           class="d-inline-block p-3" title="{{ $item['name'] ?? '' }}">
                            <img src="{{ $item['logo'] }}" alt="{{ $item['name'] ?? 'Client' }}" loading="lazy"
                                 style="max-height:2.75rem;width:auto;max-width:100%;object-fit:contain;filter:grayscale(1);opacity:.8">
                        </a>
                    @else
                        <div class="p-3">
                            <span class="fw-semibold text-secondary">{{ $item['name'] ?? '' }}</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <x-site.empty-state title="No clients listed yet" icon="ti-building-skyscraper" />
    @endif
</x-site.section>
