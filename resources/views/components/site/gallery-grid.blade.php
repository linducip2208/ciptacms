@props([
    'items' => [],
    'alt' => false,
])

@if (filled($items))
    <div class="row g-4">
        @foreach ($items as $item)
            <div class="col-6 col-md-4 col-lg-3">
                <figure class="mb-0">
                    <img src="{{ $item['src'] }}" alt="{{ $item['alt'] ?? ($item['caption'] ?? 'Gallery photo') }}"
                         loading="lazy" class="rounded w-100 lindu-thumb">
                    @if ($item['caption'] ?? null)
                        <figcaption class="text-secondary small text-center mt-1">{{ $item['caption'] }}</figcaption>
                    @endif
                </figure>
            </div>
        @endforeach
    </div>
@else
    <x-site.empty-state title="No photos yet" icon="ti-photo" />
@endif
