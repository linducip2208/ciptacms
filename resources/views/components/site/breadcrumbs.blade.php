@props(['items' => []])

@if (count($items) > 1)
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb my-0">
            @foreach ($items as $i => $item)
                @php $last = $i === array_key_last($items); @endphp
                <li class="breadcrumb-item {{ $last ? 'active' : '' }}" @if ($last) aria-current="page" @endif>
                    @if ($last || empty($item['url']))
                        {{ $item['label'] }}
                    @else
                        <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
