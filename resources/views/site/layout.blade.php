<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="{{ setting('branding.primary_color', '#1d4ed8') }}">
    <title>@yield('title', setting('general.site_name', 'Lindu CMS'))</title>
    <link rel="icon" href="{{ setting('branding.favicon', '/favicon.ico') }}">
    {!! app(\App\Core\Services\SeoService::class)->render($seo ?? null) !!}
    @if(setting('branding.custom_css'))
        <style>{!! setting('branding.custom_css') !!}</style>
    @endif
    @php $customizerCss = \App\Http\Controllers\Admin\ThemeController::cssVariables(); @endphp
    @if($customizerCss)
        <style>{!! $customizerCss !!}</style>
    @endif
    @if(setting('branding.custom_head'))
        {!! setting('branding.custom_head') !!}
    @endif
    <style>
        :root{
            --lindu-primary: {{ setting('branding.primary_color', '#1d4ed8') }};
            --lindu-secondary: {{ setting('branding.secondary_color', '#0f172a') }};
            --lindu-radius: {{ setting('branding.radius', '12px') }};
            --lindu-container: 1180px;
            --lindu-section: 64px;
        }
        *{box-sizing:border-box}
        body{margin:0;font-family:var(--lindu-font_family, system-ui,-apple-system,"Segoe UI",Roboto,sans-serif);font-size:var(--lindu-base_size,16px);color:var(--lindu-text,#0f172a);background:var(--lindu-body_bg,#fff);line-height:1.6}
        a{color:var(--lindu-primary)}
        .wrap{max-width:var(--lindu-container,1180px);margin:0 auto;padding:0 20px}
        .site-header{position:sticky;top:0;z-index:40;background:rgba(255,255,255,.92);backdrop-filter:blur(8px);border-bottom:1px solid #e5e7eb}
        .site-header .inner{display:flex;align-items:center;justify-content:space-between;height:68px;gap:16px}
        .brand{display:flex;align-items:center;gap:10px;font-weight:800;font-size:1.15rem;color:var(--lindu-secondary);text-decoration:none}
        .brand img{height:34px;width:auto}
        .nav{display:flex;gap:18px;flex-wrap:wrap;align-items:center}
        .nav a{color:#334155;text-decoration:none;font-size:.95rem;font-weight:500}
        .nav a:hover,.nav a.active{color:var(--lindu-primary)}
        .burger{display:none;background:none;border:0;font-size:1.5rem;cursor:pointer;color:var(--lindu-secondary)}
        .btn{display:inline-block;background:var(--lindu-primary);color:#fff;padding:11px 20px;border-radius:var(--lindu-radius);text-decoration:none;font-weight:600;border:0;cursor:pointer}
        .btn:hover{opacity:.9}
        .btn-outline{background:transparent;color:var(--lindu-primary);border:1px solid var(--lindu-primary)}
        section{padding:64px 0}
        .sec-alt{background:#f8fafc}
        .sec-head{text-align:center;max-width:640px;margin:0 auto 44px}
        .sec-head h2{font-size:2rem;margin:0 0 10px;color:var(--lindu-secondary)}
        .sec-head p{color:#64748b;margin:0}
        .grid{display:grid;gap:24px}
        .g2{grid-template-columns:repeat(2,1fr)}
        .g3{grid-template-columns:repeat(3,1fr)}
        .g4{grid-template-columns:repeat(4,1fr)}
        .card{background:#fff;border:1px solid #e5e7eb;border-radius:var(--lindu-radius);padding:24px}
        .card h3{margin:0 0 8px;color:var(--lindu-secondary)}
        .hero{padding:96px 0;background:linear-gradient(135deg,var(--lindu-primary),var(--lindu-secondary));color:#fff}
        .hero h1{font-size:clamp(2rem,5vw,3.4rem);margin:0 0 16px;line-height:1.15}
        .hero p{font-size:1.1rem;opacity:.92;max-width:620px}
        .stat{text-align:center;padding:20px}
        .stat b{display:block;font-size:2rem;color:var(--lindu-primary)}
        .page-title{font-size:2.2rem;margin:0 0 8px;color:var(--lindu-secondary)}
        .prose{max-width:none}
        .prose h2{margin-top:32px;color:var(--lindu-secondary)}
        .prose img{max-width:100%;height:auto;border-radius:var(--lindu-radius)}
        .prose table{width:100%;border-collapse:collapse}
        .prose table td,.prose table th{border:1px solid #e5e7eb;padding:8px}
        .site-footer{background:var(--lindu-secondary);color:#cbd5e1;padding:48px 0 24px;margin-top:0}
        .site-footer a{color:#e2e8f0}
        .foot-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:32px}
        .foot-grid h4{color:#fff;margin:0 0 12px}
        .foot-grid ul{list-style:none;padding:0;margin:0;display:grid;gap:8px}
        .foot-bottom{border-top:1px solid rgba(255,255,255,.12);margin-top:32px;padding-top:18px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;font-size:.875rem}
        .alert{padding:12px 16px;border-radius:var(--lindu-radius);margin-bottom:18px}
        .alert-ok{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
        .alert-err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
        .form-row{margin-bottom:14px}
        .form-row label{display:block;font-weight:600;margin-bottom:6px;font-size:.9rem}
        .form-row input,.form-row textarea,.form-row select{width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font-size:1rem;font-family:inherit}
        .form-row textarea{min-height:130px}
        .badge{display:inline-block;padding:3px 10px;border-radius:99px;background:#eff6ff;color:var(--lindu-primary);font-size:.75rem;font-weight:600}
        .stars{color:#f59e0b}
        .gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px}
        .gallery-grid img{width:100%;height:150px;object-fit:cover;border-radius:var(--lindu-radius);cursor:pointer}
        .filter-bar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:26px}
        .filter-bar a{padding:7px 14px;border:1px solid #cbd5e1;border-radius:99px;text-decoration:none;color:#475569;font-size:.875rem}
        .filter-bar a.active{background:var(--lindu-primary);color:#fff;border-color:var(--lindu-primary)}
        .breadcrumb{font-size:.875rem;color:#64748b;padding:16px 0}
        .breadcrumb ol{list-style:none;display:flex;gap:8px;padding:0;margin:0;flex-wrap:wrap}
        .breadcrumb li+li:before{content:"/";margin-right:8px;color:#cbd5e1}
        .empty{text-align:center;padding:56px 20px;color:#94a3b8}
        .toc{background:#f8fafc;border:1px solid #e5e7eb;border-radius:var(--lindu-radius);padding:18px 22px;margin-bottom:28px}
        .toc h4{margin:0 0 8px}
        .toc ol{margin:0;padding-left:20px}
        @media(max-width:900px){
            .g4{grid-template-columns:repeat(2,1fr)}
            .g3{grid-template-columns:repeat(2,1fr)}
            .foot-grid{grid-template-columns:1fr 1fr}
            .burger{display:block}
            .nav{display:none;position:absolute;top:68px;left:0;right:0;background:#fff;border-bottom:1px solid #e5e7eb;flex-direction:column;align-items:flex-start;padding:16px 20px;gap:12px}
            .nav.open{display:flex}
        }
        @media(max-width:600px){
            section{padding:44px 0}
            .g2,.g3,.g4{grid-template-columns:1fr}
        }
    </style>
    @stack('head')
</head>
<body>
<header class="site-header">
    <div class="wrap inner">
        <a class="brand" href="{{ route('site.home') }}">
            @if(setting('branding.logo'))
                <img src="{{ setting('branding.logo') }}" alt="{{ setting('general.site_name', 'Lindu CMS') }}">
            @else
                <span aria-hidden="true">✦</span>
            @endif
            <span>{{ setting('general.site_name', 'Lindu CMS') }}</span>
        </a>
        <button class="burger" onclick="document.querySelector('.nav').classList.toggle('open')" aria-label="Menu">☰</button>
        <nav class="nav">
            @include('site.partials.nav')
        </nav>
    </div>
</header>

<main>
    @if(session('ok'))
        <div class="wrap"><div class="alert alert-ok mt-4">{{ session('ok') }}</div></div>
    @endif
    @if($errors->any())
        <div class="wrap"><div class="alert alert-err mt-4">
            <ul style="margin:0;padding-left:18px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div></div>
    @endif

    @yield('content')
</main>

<footer class="site-footer">
    <div class="wrap">
        <div class="foot-grid">
            <div>
                <h4>{{ setting('general.site_name', 'Lindu CMS') }}</h4>
                <p>{{ \Illuminate\Support\Str::limit(strip_tags((string) ($about['about.description'] ?? '')), 180) }}</p>
                @if(!empty($contactInfo['contact.address']))
                    <p>{{ $contactInfo['contact.address'] }}</p>
                @endif
            </div>
            <div>
                <h4>Company</h4>
                <ul>
                    <li><a href="{{ route('site.about') }}">About</a></li>
                    <li><a href="{{ route('site.team') }}">Team</a></li>
                    <li><a href="{{ route('site.portfolio') }}">Portfolio</a></li>
                    <li><a href="{{ route('site.careers') }}">Careers</a></li>
                </ul>
            </div>
            <div>
                <h4>Services</h4>
                <ul>
                    <li><a href="{{ route('site.services') }}">All Services</a></li>
                    <li><a href="{{ route('site.products') }}">Products</a></li>
                    <li><a href="{{ route('site.faq') }}">FAQ</a></li>
                    <li><a href="{{ route('site.gallery') }}">Gallery</a></li>
                </ul>
            </div>
            <div>
                <h4>Contact</h4>
                <ul>
                    @if(!empty($contactInfo['contact.email']))
                        <li><a href="mailto:{{ $contactInfo['contact.email'] }}">{{ $contactInfo['contact.email'] }}</a></li>
                    @endif
                    @if(!empty($contactInfo['contact.phone']))
                        <li>{{ $contactInfo['contact.phone'] }}</li>
                    @endif
                    @if(!empty($contactInfo['contact.whatsapp']))
                        <li><a href="https://wa.me/{{ ltrim($contactInfo['contact.whatsapp'], '+') }}" rel="noopener">WhatsApp</a></li>
                    @endif
                    <li><a href="{{ route('site.contact') }}">Contact Form</a></li>
                </ul>
            </div>
        </div>
        <div class="foot-bottom">
            <span>&copy; {{ date('Y') }} {{ setting('general.site_name', 'Lindu CMS') }}. {{ setting('general.footer', 'All rights reserved.') }}</span>
            <span>
                @foreach($contactInfo['contact.social'] ?? [] as $network => $url)
                    <a href="{{ $url }}" rel="noopener nofollow" target="_blank" style="margin-left:10px">{{ ucfirst($network) }}</a>
                @endforeach
            </span>
        </div>
    </div>
</footer>

@if(setting('branding.custom_js'))
    <script>{!! setting('branding.custom_js') !!}</script>
@endif
@if(setting('branding.custom_footer'))
    {!! setting('branding.custom_footer') !!}
@endif
@stack('scripts')
</body>
</html>
