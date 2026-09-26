<!DOCTYPE html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}" data-bs-theme="light" x-data="{sidebar:true}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title','Dashboard') | {{ setting('general.site_name','Lindu CMS') }}</title>
<link rel="manifest" href="/manifest.webmanifest"><meta name="theme-color" content="#206bc4">
{{-- Tabler, its icon font and Alpine are bundled locally. A CDN outage must
     not be able to take the admin panel — or the page builder — down. --}}
@vite(['resources/css/tabler.css', 'resources/js/app.js'])
<style>
.nav-link.active{background:#206bc4;color:#fff!important;border-radius:.5rem}
.nav-link{border-radius:.5rem}
/* shims agar view lama tetap jalan di atas Tabler */
.btn-primary{background:#206bc4;color:#fff;padding:.5rem 1rem;border-radius:.5rem;font-size:.85rem;border:0}
.btn-primary:hover{background:#1a5fb0;color:#fff}
.input{width:100%;border:1px solid #d9dee6;border-radius:.5rem;padding:.5rem .75rem;font-size:.875rem;background:#fff}
table.tbl{width:100%;font-size:.85rem}table.tbl th{text-align:left;padding:.6rem;color:#667382;font-weight:600;border-bottom:1px solid #e6e9f2}table.tbl td{padding:.6rem;border-bottom:1px solid #f0f2f7}
.badge{background:#e8effd;color:#206bc4}
.card{border-radius:.75rem}
</style>
@stack('head')</head>
<body>
<div class="page">
<aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="light">
<div class="container-fluid">
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu"><span class="navbar-toggler-icon"></span></button>
<h1 class="navbar-brand navbar-brand-autodark"><span class="badge bg-blue text-white me-1">L</span> {{ setting('general.site_name','Lindu CMS') }} <small class="text-muted">v{{ config('lindu.version') }}</small></h1>
<div class="collapse navbar-collapse" id="sidebar-menu">
    @include('admin.partials.sidebar-menu')
</div>
</div>
</aside>
<header class="navbar navbar-expand-md d-print-none">
<div class="container-xl">
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu"><span class="navbar-toggler-icon"></span></button>
<form action="{{ route('admin.search') }}" class="d-none d-md-flex me-3"><div class="input-group"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Search…"><button class="btn btn-primary">Go</button></div></form>
<div class="navbar-nav flex-row order-md-last">
<div class="nav-item dropdown" x-data="{open:false}"><a href="#" @click.prevent="open=!open" class="nav-link d-flex lh-1 text-reset p-0"><span class="avatar avatar-sm">{{ substr(auth()->user()->name??'A',0,1) }}</span><div class="d-none d-xl-block ps-2"><div>{{ auth()->user()->name }}</div><div class="text-muted mt-1 small">{{ auth()->user()->email }}</div></div></a>
<div x-show="open" @click.outside="open=false" class="dropdown-menu dropdown-menu-end show position-absolute mt-2"><a class="dropdown-item" href="{{ route('admin.sessions.index') }}">Sessions</a><a class="dropdown-item" href="{{ route('admin.security.2fa') }}">2FA</a><form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-item text-danger">Logout</button></form></div></div>
</div>
</div>
</header>
<div class="page-wrapper">
<div class="page-header d-print-none"><div class="container-xl"><div class="row g-2 align-items-center"><div class="col"><div class="page-pretitle"><a href="{{ route('admin.dashboard') }}">Home</a> / @yield('crumb','')</div><h2 class="page-title">@yield('title','Dashboard')</h2></div></div></div></div>
<div class="page-body"><div class="container-xl">
@if(session('ok'))<div class="alert alert-success">{{ session('ok') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
@yield('content')
</div></div>
</div>
</div>
@vite(['resources/js/tabler.js'])
@stack('scripts')</body></html>
