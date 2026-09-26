@php
    $primaryMenu = [];
    try {
        $primaryMenu = app(\App\Core\Services\MenuService::class)->tree('primary', auth()->user());
    } catch (\Throwable $e) {
        $primaryMenu = [];
    }
    $path = trim(parse_url(request()->url(), PHP_URL_PATH) ?? '', '/');
    $segments = $path === '' ? [] : explode('/', $path);
@endphp

@forelse($primaryMenu as $item)
    @php
        $href = $item['url'] ?? ($item['route'] ?? null ? route($item['route']) : '#');
        $itemPath = trim(parse_url($href, PHP_URL_PATH) ?? '', '/');
        $first = $segments[0] ?? '';
        $isActive = $first !== '' && ($itemPath === $first || str_starts_with($itemPath, $first.'/'));
    @endphp
    <a href="{{ $href }}" class="{{ $isActive ? 'active' : '' }}"
       @if(($item['target'] ?? '_self') === '_blank') target="_blank" rel="noopener" @endif>
        {{ $item['title'] }}
        @if(!empty($item['children']))
            <span aria-hidden="true">▾</span>
        @endif
    </a>
@empty
    {{-- No database-driven menu yet: render the core section links so the site is navigable. --}}
    <a href="{{ route('site.about') }}" class="{{ request()->routeIs('site.about') ? 'active' : '' }}">About</a>
    <a href="{{ route('site.services') }}" class="{{ request()->routeIs('site.services', 'site.service') ? 'active' : '' }}">Services</a>
    <a href="{{ route('site.products') }}" class="{{ request()->routeIs('site.products', 'site.product') ? 'active' : '' }}">Products</a>
    <a href="{{ route('site.portfolio') }}" class="{{ request()->routeIs('site.portfolio*') ? 'active' : '' }}">Portfolio</a>
    <a href="{{ route('site.blog') }}" class="{{ request()->routeIs('site.blog', 'site.post') ? 'active' : '' }}">Blog</a>
    <a href="{{ route('site.contact') }}" class="{{ request()->routeIs('site.contact') ? 'active' : '' }}">Contact</a>
@endforelse
