@props([
    'count' => 0,
    'icon' => 'ti-inbox',
    'title' => 'Nothing here yet',
    'message' => null,
    'action' => null,   // ['label' => 'Create', 'url' => '#']
])

{{--
    Shared empty state for admin list screens.

    A table with no rows is a dead end: the operator cannot tell "nothing
    matches your filter" from "you have not created anything yet". Every
    paginated admin list renders this instead of a bare table.
--}}
@if ((int) $count === 0)
    <tr>
        <td colspan="{{ $attributes->get('colspan', 1) }}">
            <div class="text-center text-muted py-5">
                <div class="display-6 mb-2 opacity-50"><i class="ti {{ $icon }}"></i></div>
                <p class="h4 mb-1">{{ $title }}</p>
                @if ($message)
                    <p class="mb-3">{{ $message }}</p>
                @endif
                @if ($action && ! empty($action['url']))
                    <a href="{{ $action['url'] }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>{{ $action['label'] }}
                    </a>
                @endif
            </div>
        </td>
    </tr>
@endif
