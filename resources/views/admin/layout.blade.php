<!DOCTYPE html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}" data-bs-theme="light" x-data="{sidebar:true}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title','Dashboard') | {{ setting('general.site_name','Lindu CMS') }}</title>
<link rel="manifest" href="/manifest.webmanifest"><meta name="theme-color" content="#206bc4">
<link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" rel="stylesheet">
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
<ul class="navbar-nav pt-lg-3">
<li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.dashboard')?'active':'' }}" href="{{ route('admin.dashboard') }}"><span class="nav-link-icon"><i class="ti ti-home"></i></span> Dashboard</a></li>
<li class="nav-item mt-2"><small class="text-muted px-3">CONTENT</small></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.cms.pages') }}"><span class="nav-link-icon"><i class="ti ti-file"></i></span> Pages</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.cms.posts') }}"><span class="nav-link-icon"><i class="ti ti-pencil"></i></span> Posts</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.cms.comments') }}"><span class="nav-link-icon"><i class="ti ti-messages"></i></span> Comments</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.media') }}"><span class="nav-link-icon"><i class="ti ti-photo"></i></span> Media</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.cms.seo') }}"><span class="nav-link-icon"><i class="ti ti-search"></i></span> SEO</a></li>
<li class="nav-item mt-2"><small class="text-muted px-3">BUILDERS</small></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.cms.forms') }}"><span class="nav-link-icon"><i class="ti ti-forms"></i></span> Forms</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.cms.types') }}"><span class="nav-link-icon"><i class="ti ti-blocks"></i></span> Data Types</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.cms.workflows') }}"><span class="nav-link-icon"><i class="ti ti-bolt"></i></span> Workflows</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.cms.webhooks') }}"><span class="nav-link-icon"><i class="ti ti-unlink"></i></span> Webhooks</a></li>
<li class="nav-item mt-2"><small class="text-muted px-3">COMMERCE</small></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.resource.index','products') }}"><span class="nav-link-icon"><i class="ti ti-package"></i></span> Products</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.resource.index','orders') }}"><span class="nav-link-icon"><i class="ti ti-receipt"></i></span> Orders</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.resource.index','customers') }}"><span class="nav-link-icon"><i class="ti ti-users"></i></span> Customers</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.resource.index','sales') }}"><span class="nav-link-icon"><i class="ti ti-calculator"></i></span> POS Sales</a></li>
<li class="nav-item mt-2"><small class="text-muted px-3">APPS</small></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.resource.index','leads') }}"><span class="nav-link-icon"><i class="ti ti-target"></i></span> CRM Leads</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.resource.index','courses') }}"><span class="nav-link-icon"><i class="ti ti-school"></i></span> Courses</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.resource.index','properties') }}"><span class="nav-link-icon"><i class="ti ti-building"></i></span> Properties</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.resource.index','vendors') }}"><span class="nav-link-icon"><i class="ti ti-store"></i></span> Vendors</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.resource.index','members') }}"><span class="nav-link-icon"><i class="ti ti-heart"></i></span> Members</a></li>
<li class="nav-item mt-2"><small class="text-muted px-3">SYSTEM</small></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.users') }}"><span class="nav-link-icon"><i class="ti ti-user"></i></span> Users</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.roles') }}"><span class="nav-link-icon"><i class="ti ti-shield"></i></span> Roles</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.menus') }}"><span class="nav-link-icon"><i class="ti ti-menu"></i></span> Menus</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.modules') }}"><span class="nav-link-icon"><i class="ti ti-box"></i></span> Modules</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.plugins') }}"><span class="nav-link-icon"><i class="ti ti-plug"></i></span> Plugins</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.themes') }}"><span class="nav-link-icon"><i class="ti ti-palette"></i></span> Themes</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.tenants') }}"><span class="nav-link-icon"><i class="ti ti-building-skyscraper"></i></span> Tenants</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.plans') }}"><span class="nav-link-icon"><i class="ti ti-credit-card"></i></span> Plans</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.licenses') }}"><span class="nav-link-icon"><i class="ti ti-key"></i></span> Licenses</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.gateways') }}"><span class="nav-link-icon"><i class="ti ti-wallet"></i></span> Payments</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.2fa') }}"><span class="nav-link-icon"><i class="ti ti-lock"></i></span> 2FA</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.sessions') }}"><span class="nav-link-icon"><i class="ti ti-devices"></i></span> Sessions</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.settings') }}"><span class="nav-link-icon"><i class="ti ti-settings"></i></span> Settings</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.api-docs') }}"><span class="nav-link-icon"><i class="ti ti-api"></i></span> API Docs</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.health') }}"><span class="nav-link-icon"><i class="ti ti-heartbeat"></i></span> Health</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.queue') }}"><span class="nav-link-icon"><i class="ti ti-clock"></i></span> Queue</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.audits') }}"><span class="nav-link-icon"><i class="ti ti-list"></i></span> Audit Log</a></li>
<li class="nav-item"><a class="nav-link" href="{{ route('admin.backups') }}"><span class="nav-link-icon"><i class="ti ti-database"></i></span> Backups</a></li>
</ul>
</div>
</div>
</aside>
<header class="navbar navbar-expand-md d-print-none">
<div class="container-xl">
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu"><span class="navbar-toggler-icon"></span></button>
<form action="{{ route('admin.search') }}" class="d-none d-md-flex me-3"><div class="input-group"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Search…"><button class="btn btn-primary">Go</button></div></form>
<div class="navbar-nav flex-row order-md-last">
<div class="nav-item dropdown" x-data="{open:false}"><a href="#" @click.prevent="open=!open" class="nav-link d-flex lh-1 text-reset p-0"><span class="avatar avatar-sm">{{ substr(auth()->user()->name??'A',0,1) }}</span><div class="d-none d-xl-block ps-2"><div>{{ auth()->user()->name }}</div><div class="text-muted mt-1 small">{{ auth()->user()->email }}</div></div></a>
<div x-show="open" @click.outside="open=false" class="dropdown-menu dropdown-menu-end show position-absolute mt-2"><a class="dropdown-item" href="{{ route('admin.sessions') }}">Sessions</a><a class="dropdown-item" href="{{ route('admin.2fa') }}">2FA</a><form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-item text-danger">Logout</button></form></div></div>
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
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/js/tabler.min.js"></script>
@stack('scripts')</body></html>
