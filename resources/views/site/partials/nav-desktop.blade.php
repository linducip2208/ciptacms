{{--
    Desktop navigation. Expects $navMain and $lindu_nav_active() from
    nav-state.blade.php, so the active-state rule is not repeated here.
--}}
<ul class="navbar-nav ms-auto mb-0 mb-md-3 align-items-md-center">
    @foreach ($navMain as $item)
        @php
            $hasChildren = filled($item['children'] ?? []);
            $active = lindu_nav_active($item);
            $id = 'nav-' . ($item['id'] ?? 'x');
        @endphp

        @if ($hasChildren)
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle {{ $active ? 'active' : '' }}"
                   id="{{ $id }}"
                   href="#{{ $id }}" role="button" data-bs-toggle="dropdown"
                   aria-expanded="false"
                   @if ($active) style="color:var(--tblr-primary);font-weight:600" aria-current="true" @endif>
                    {{ $item['title'] }}
                </a>
                <div class="dropdown-menu shadow-sm" aria-labelledby="{{ $id }}">
                    @foreach ($item['children'] as $child)
                        <a class="dropdown-item {{ lindu_nav_active($child) ? 'active' : '' }}"
                           href="{{ $child['url'] ?? '#' }}"
                           @if (lindu_nav_active($child)) aria-current="page" @endif
                           @if (($child['target'] ?? '_self') === '_blank') target="_blank" rel="noopener" @endif>
                            {{ $child['title'] }}
                        </a>
                    @endforeach
                </div>
            </li>
        @else
            <li class="nav-item">
                <a class="nav-link {{ $active ? 'active fw-semibold' : '' }}"
                   href="{{ $item['url'] ?? '#' }}"
                   @if ($active) style="color:var(--tblr-primary)" aria-current="page" @endif
                   @if (($item['target'] ?? '_self') === '_blank') target="_blank" rel="noopener" @endif>
                    {{ $item['title'] }}
                </a>
            </li>
        @endif
    @endforeach

    @if ($navCta)
        <li class="nav-item ms-md-2">
            <a class="btn btn-primary" href="{{ $navCta['url'] ?? '#' }}">{{ $navCta['title'] }}</a>
        </li>
    @endif
</ul>
