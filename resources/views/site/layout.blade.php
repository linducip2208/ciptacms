<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-bs-theme="{{ \App\Http\Controllers\Admin\ThemeController::siteColorMode() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ setting('branding.primary_color', '#1d4ed8') }}">

    <title>@yield('title', setting('general.site_name', 'Lindu CMS'))</title>
    <link rel="icon" href="{{ setting('branding.favicon', '/favicon.ico') }}">
    <link rel="manifest" href="/manifest.webmanifest">

    {{--
        One bundle for the whole product: Tabler is the design foundation,
        Tailwind is the utility layer, and the CMS branding tokens arrive in
        tokenCss() just below. Nothing here is loaded from a CDN.
    --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @php
        // Live branding: logo, favicon, colours, fonts, radius, container
        // width. Compiled-in defaults are the fallback, not the source.
        $tokens = \App\Http\Controllers\Admin\ThemeController::tokenCss();
    @endphp
    @if ($tokens !== '')
        <style>{!! $tokens !!}</style>
    @endif

    {!! app(\App\Core\Services\SeoService::class)->render($seo ?? null) !!}

    @if (setting('branding.custom_css'))
        <style>{!! setting('branding.custom_css') !!}</style>
    @endif
    @if (setting('branding.custom_head'))
        {!! setting('branding.custom_head') !!}
    @endif

    @stack('head')
</head>
<body>
    <a href="#main" class="visually-hidden-focusable">Skip to content</a>

    @php
        // The Menu Engine is the only source of the public navigation. The
        // navbar partial renders whatever tree it is handed.
        $items = app(\App\Core\Services\MenuService::class)->tree('primary');
    @endphp
    @include('site.partials.nav')

    <main id="main">
        @include('site.partials.flash')
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <x-site.footer />

    {{-- Tabler's JS powers the navbar dropdowns and the offcanvas. --}}
    @vite(['resources/js/tabler.js'])

    @if (setting('branding.custom_js'))
        <script>{!! setting('branding.custom_js') !!}</script>
    @endif
    @if (setting('branding.custom_footer'))
        {!! setting('branding.custom_footer') !!}
    @endif
    @stack('scripts')
</body>
</html>
