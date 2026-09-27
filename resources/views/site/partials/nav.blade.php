@php
    /**
     * Public navigation.
     *
     * The Menu Engine is the only source of truth: this partial never names a
     * page. Items with children render as Tabler dropdowns, an item flagged
     * meta.cta renders as a button, and everything is mirrored into the
     * offcanvas menu for small screens.
     *
     * @var array $items  tree from MenuService::tree('primary')
     */
    $isActive = function (?array $item) use (&$isActive): bool {
        $path = ltrim(parse_url((string) ($item['url'] ?? '/'), PHP_URL_PATH) ?: '/', '/');
        $current = trim(parse_url(request()->url(), PHP_URL_PATH) ?: '/', '/');

        if ($path === $current) {
            return true;
        }

        foreach ($item['children'] ?? [] as $child) {
            if ($isActive($child)) {
                return true;
            }
        }

        return $path !== '' && str_starts_with($current, $path);
    };

    $top = collect($items)->filter(fn ($i) => ($i['is_visible'] ?? true) !== false);
    $cta = $top->first(fn ($i) => data_get($i, 'meta.cta'));
    $main = $top->reject(fn ($i) => data_get($i, 'meta.cta'));
@endphp

<header class="lindu-header">
<nav class="navbar navbar-expand-md sticky-top" aria-label="Main navigation">
    <div class="container-xl">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('site.home') }}">
            @if (setting('branding.logo'))
                <img src="{{ setting('branding.logo') }}" alt="{{ setting('general.site_name', 'Lindu CMS') }}"
                     style="height:2rem;width:auto" loading="eager">
            @endif
            <span>{{ setting('general.site_name', 'Lindu CMS') }}</span>
        </a>

        <button class="navbar-toggler border-0 px-0" type="button" data-bs-toggle="offcanvas"
                data-bs-target="#linduMobileNav" aria-controls="linduMobileNav" aria-label="Open menu">
            <i class="ti ti-menu fs-2"></i>
        </button>

        <div class="collapse navbar-collapse">
            @if ($main->isNotEmpty())
                <ul class="navbar-nav ms-auto mb-0 mb-md-3 align-items-md-center">
                    @foreach ($main as $item)
                        @php
                            $hasChildren = filled($item['children'] ?? []);
                            $active = $isActive($item);
                            $id = 'nav-' . ($item['id'] ?? 'x');
                        @endphp

                        @if ($hasChildren)
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle {{ $active ? 'active' : '' }}"
                                   href="#{{ $id }}" role="button" data-bs-toggle="dropdown"
                                    aria-expanded="false" @if ($active) style="color:var(--tblr-primary);font-weight:600" aria-current="true" @endif>
                                    {{ $item['title'] }}
                                </a>
                                <div class="dropdown-menu shadow-sm" aria-labelledby="{{ $id }}">
                                    @foreach ($item['children'] as $child)
                                        <a class="dropdown-item {{ $isActive($child) ? 'active' : '' }}"
                                           href="{{ $child['url'] ?? '#' }}"
                                           @if ($isActive($child)) aria-current="page" @endif
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

                    @if ($cta)
                        <li class="nav-item ms-md-2">
                            <a class="btn btn-primary" href="{{ $cta['url'] ?? '#' }}">{{ $cta['title'] }}</a>
                        </li>
                    @endif
                </ul>
            @else
                {{-- No menu configured yet: show the primary call to action only,
                     so the header is never an empty bar. --}}
                <div class="ms-auto">
                    @if ($cta)
                        <a class="btn btn-primary" href="{{ $cta['url'] ?? '#' }}">{{ $cta['title'] }}</a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</nav>
</header>

{{-- Offcanvas: the same tree, rendered for small screens. --}}
<div class="offcanvas offcanvas-start" tabindex="-1" id="linduMobileNav" aria-labelledby="linduMobileNavLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="linduMobileNavLabel">{{ setting('general.site_name', 'Lindu CMS') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <ul class="list-unstyled mb-0">
            @foreach ($top as $item)
                @php $hasChildren = filled($item['children'] ?? []); @endphp

                <li class="mb-2">
                    @if ($hasChildren)
                        <div class="fw-semibold text-secondary small text-uppercase mb-1"
                             style="lindu-micro-label">
                            {{ $item['title'] }}
                        </div>
                        <ul class="list-unstyled ps-2">
                            @foreach ($item['children'] as $child)
                                <li>
                                    <a class="d-block py-1 text-decoration-none {{ $isActive($child) ? 'fw-semibold' : '' }}"
                                       href="{{ $child['url'] ?? '#' }}"
                                       @if ($isActive($child)) style="color:var(--tblr-primary)" aria-current="page" @endif
                                       @if (($child['target'] ?? '_self') === '_blank') target="_blank" rel="noopener" @endif>
                                        {{ $child['title'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <a class="d-block py-1 text-decoration-none {{ $isActive($item) ? 'fw-semibold' : '' }}"
                           href="{{ $item['url'] ?? '#' }}"
                           @if ($isActive($item)) style="color:var(--tblr-primary)" aria-current="page" @endif
                           @if (($item['target'] ?? '_self') === '_blank') target="_blank" rel="noopener" @endif>
                            {{ $item['title'] }}
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>

        @if ($cta)
            <a class="btn btn-primary w-100 mt-4" href="{{ $cta['url'] ?? '#' }}">{{ $cta['title'] }}</a>
        @endif
    </div>
</div>
