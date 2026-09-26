@foreach($items as $item)
    @php
        $hasChildren = !empty($item['children']);
        $isActive = false;
        if (!empty($item['route'])) {
            $isActive = request()->routeIs($item['route']);
        } elseif (!empty($item['url'])) {
            $isActive = request()->is(ltrim($item['url'], '/').'*');
        }
    @endphp
    
    @if($hasChildren)
        <li class="nav-item">
            <a class="nav-link {{ $isActive ? 'active' : '' }}" href="#"
               data-bs-toggle="collapse" data-bs-target="#menu-{{ $item['id'] }}"
               aria-expanded="{{ $isActive ? 'true' : 'false' }}">
                <span class="nav-link-icon">
                    @if($item['icon'])
                        <i class="{{ $item['icon'] }}"></i>
                    @else
                        <i class="ti ti-chevron-right"></i>
                    @endif
                </span>
                {{ $item['title'] }}
                @if(!empty($item['badge']))
                    <span class="badge bg-{{ $item['badge_color'] ?? 'blue' }} ms-auto">{{ $item['badge'] }}</span>
                @endif
            </a>
            <div class="collapse {{ $isActive ? 'show' : '' }}" id="menu-{{ $item['id'] }}">
                <ul class="navbar-nav ps-3">
                    @include('admin.partials.menu-tree', ['items' => $item['children']])
                </ul>
            </div>
        </li>
    @else
        <li class="nav-item">
            <a class="nav-link {{ $isActive ? 'active' : '' }}"
               href="{{ $item['url'] ?? ($item['route'] ? route($item['route']) : '#') }}"
               target="{{ $item['target'] ?? '_self' }}">
                <span class="nav-link-icon">
                    @if($item['icon'])
                        <i class="{{ $item['icon'] }}"></i>
                    @else
                        <i class="ti ti-chevron-right"></i>
                    @endif
                </span>
                {{ $item['title'] }}
                @if(!empty($item['badge']))
                    <span class="badge bg-{{ $item['badge_color'] ?? 'blue' }} ms-auto">{{ $item['badge'] }}</span>
                @endif
            </a>
        </li>
    @endif
@endforeach