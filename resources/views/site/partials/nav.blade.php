{{--
    Public navigation.

    The Menu Engine is the only source of truth.

    nav-state.blade.php defines the shared active-state helper as a real
    function, so desktop and mobile can never disagree about which item is
    current. The item lists are split here, in the parent, because Blade's
    @include does not bubble variables back up to the including view.
--}}
@include('site.partials.nav-state')

@php
    $navItems = collect($items ?? [])
        ->filter(fn ($i) => ($i['is_visible'] ?? true) !== false);

    $navCta = $navItems->first(fn ($i) => data_get($i, 'meta.cta'));
    $navMain = $navItems->reject(fn ($i) => data_get($i, 'meta.cta'));
@endphp

<header class="lindu-header">
    <nav class="navbar navbar-expand-md sticky-top" aria-label="Main navigation">
        <div class="container-xl">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="{{ route('site.home') }}">
                @if (setting('branding.logo'))
                    <img src="{{ setting('branding.logo') }}" alt="{{ setting('general.site_name', 'Lindu CMS') }}"
                         class="lindu-logo-mark">
                @endif
                <span>{{ setting('general.site_name', 'Lindu CMS') }}</span>
            </a>

            <button class="navbar-toggler border-0 px-0" type="button" data-bs-toggle="offcanvas"
                    data-bs-target="#linduMobileNav" aria-controls="linduMobileNav" aria-label="Open menu">
                <i class="ti ti-menu fs-2"></i>
            </button>

            <div class="collapse navbar-collapse">
                @if ($navMain->isNotEmpty())
                    @include('site.partials.nav-desktop', ['navMain' => $navMain, 'navCta' => $navCta])
                @else
                    {{-- No menu configured yet: keep the header from being an
                         empty bar by showing only the call to action. --}}
                    <div class="ms-auto">
                        @if ($navCta)
                            <a class="btn btn-primary" href="{{ $navCta['url'] ?? '#' }}">{{ $navCta['title'] }}</a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </nav>
</header>

@include('site.partials.nav-mobile', ['navMain' => $navMain, 'navCta' => $navCta])
