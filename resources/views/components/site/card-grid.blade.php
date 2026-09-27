@props([
    'items' => [],
    'columns' => 3,
])

@if (filled($items))
    <div class="row g-4">
        @foreach ($items as $item)
            @php
                $href = $item['url'] ?? null;
                $tag = $href ? 'a' : 'div';
            @endphp
            <div class="col-md-6 col-lg-{{ 12 / max(1, min(4, $columns)) }}">
                <{{ $tag }}
                    @if ($href) href="{{ $href }}" @endif
                    class="card h-100 text-decoration-none {{ $href ? 'card-link' : '' }}"
                >
                    @if ($item['image'] ?? null)
                        <img src="{{ $item['image'] }}" alt="{{ $item['imageAlt'] ?? ($item['title'] ?? '') }}"
                             class="card-img-top lindu-thumb" loading="lazy">
                    @endif

                    <div class="card-body d-flex flex-column">
                        @if ($item['icon'] ?? null)
                            <div class="fs-1 mb-2" style="color:var(--tblr-primary)">{{ $item['icon'] }}</div>
                        @endif

                        @if ($item['title'] ?? null)
                            <h3 class="card-title h4">{{ $item['title'] }}</h3>
                        @endif

                        @if ($item['excerpt'] ?? null)
                            <p class="text-secondary flex-grow-1">
                                {{ \Illuminate\Support\Str::limit(strip_tags((string) $item['excerpt']), 130) }}
                            </p>
                        @endif

                        @if ($href)
                            <span class="text-primary fw-semibold mt-auto">
                                {{ $item['cta'] ?? 'Learn more' }} <i class="ti ti-arrow-right ms-1"></i>
                            </span>
                        @endif
                    </div>
                </{{ $tag }}>
            </div>
        @endforeach
    </div>
@else
    <x-site.empty-state :title="$emptyTitle ?? 'Nothing here yet'" :message="$emptyMessage ?? null" />
@endif
