@props(['items' => [], 'alt' => false])

@if (filled($items))
    <div class="row g-4 text-center">
        @foreach ($items as $item)
            <div class="col-6 col-lg-{{ 12 / max(1, count($items) > 4 ? 4 : count($items)) }}">
                <div class="lindu-stat-value">{{ $item['value'] }}</div>
                <div class="text-secondary mt-1">{{ $item['label'] }}</div>
            </div>
        @endforeach
    </div>
@endif
