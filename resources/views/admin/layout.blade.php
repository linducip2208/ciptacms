<!DOCTYPE html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}" x-data="{dark: localStorage.getItem('lindu-dark')==='1', sidebar: window.innerWidth>1024, palette:false}" :class="{'dark':dark}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title','Dashboard') | {{ setting('general.site_name','Lindu CMS') }}</title>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>tailwind.config={darkMode:'class'}</script>
<style>body{font-family:Inter,system-ui,sans-serif}.sidebar-link{display:flex;align-items:center;gap:.6rem;padding:.5rem .8rem;border-radius:.6rem;color:#334155;font-size:.875rem}.dark .sidebar-link{color:#cbd5e1}.sidebar-link:hover{background:#f1f5f9}.dark .sidebar-link:hover{background:#1e293b}.sidebar-link.active{background:#4f46e5;color:#fff}.card{background:#fff;border:1px solid #e2e8f0;border-radius:.9rem}.dark .card{background:#0f172a;border-color:#1e293b}.btn-primary{background:#4f46e5;color:#fff;padding:.5rem 1rem;border-radius:.6rem;font-size:.85rem}.btn-primary:hover{background:#4338ca}.input{width:100%;border:1px solid #cbd5e1;border-radius:.6rem;padding:.5rem .75rem;font-size:.875rem;background:#fff}.dark .input{background:#020617;border-color:#334155;color:#e2e8f0}table.tbl{width:100%;font-size:.85rem}table.tbl th{text-align:left;padding:.6rem;color:#64748b;font-weight:600;border-bottom:1px solid #e2e8f0}table.tbl td{padding:.6rem;border-bottom:1px solid #f1f5f9}.badge{font-size:.7rem;padding:.15rem .5rem;border-radius:999px;background:#eef2ff;color:#4f46e5}</style>
@stack('head')</head>
<body class="bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-200">
<div class="flex min-h-screen">
<aside class="fixed lg:static z-40 h-screen w-64 shrink-0 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 p-4 overflow-y-auto" x-show="sidebar" x-transition>
<div class="flex items-center gap-2 mb-6"><div class="w-9 h-9 rounded-xl bg-indigo-600 text-white grid place-items-center font-bold">L</div><div><div class="font-bold">{{ setting('general.site_name','Lindu CMS') }}</div><div class="text-xs text-slate-500">v{{ config('lindu.version') }}</div></div></div>
<nav class="space-y-1">
<a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard')?'active':'' }}">🏠 Dashboard</a>
<div class="text-[11px] uppercase text-slate-400 mt-4 mb-1">Content</div>
<a href="{{ route('admin.cms.pages') }}" class="sidebar-link">📄 Pages</a>
<a href="{{ route('admin.cms.posts') }}" class="sidebar-link">✏️ Posts</a>
<a href="{{ route('admin.cms.comments') }}" class="sidebar-link">💬 Comments</a>
<a href="{{ route('admin.media') }}" class="sidebar-link">🖼️ Media</a>
<a href="{{ route('admin.cms.seo') }}" class="sidebar-link">🔍 SEO</a>
<div class="text-[11px] uppercase text-slate-400 mt-4 mb-1">Builders</div>
<a href="{{ route('admin.cms.forms') }}" class="sidebar-link">📝 Forms</a>
<a href="{{ route('admin.cms.types') }}" class="sidebar-link">🧱 Data Types</a>
<a href="{{ route('admin.cms.workflows') }}" class="sidebar-link">⚡ Workflows</a>
<a href="{{ route('admin.cms.webhooks') }}" class="sidebar-link">🔗 Webhooks</a>
<div class="text-[11px] uppercase text-slate-400 mt-4 mb-1">Commerce</div>
<a href="{{ route('admin.resource.index','products') }}" class="sidebar-link">📦 Products</a>
<a href="{{ route('admin.resource.index','orders') }}" class="sidebar-link">🧾 Orders</a>
<a href="{{ route('admin.resource.index','customers') }}" class="sidebar-link">👥 Customers</a>
<a href="{{ route('admin.resource.index','sales') }}" class="sidebar-link">🧮 POS Sales</a>
<div class="text-[11px] uppercase text-slate-400 mt-4 mb-1">Apps</div>
<a href="{{ route('admin.resource.index','leads') }}" class="sidebar-link">🎯 CRM Leads</a>
<a href="{{ route('admin.resource.index','courses') }}" class="sidebar-link">🎓 Courses</a>
<a href="{{ route('admin.resource.index','properties') }}" class="sidebar-link">🏨 Properties</a>
<a href="{{ route('admin.resource.index','vendors') }}" class="sidebar-link">🏪 Vendors</a>
<a href="{{ route('admin.resource.index','members') }}" class="sidebar-link">❤️ Members</a>
<div class="text-[11px] uppercase text-slate-400 mt-4 mb-1">System</div>
<a href="{{ route('admin.users') }}" class="sidebar-link">👤 Users</a>
<a href="{{ route('admin.roles') }}" class="sidebar-link">🛡️ Roles</a>
<a href="{{ route('admin.menus') }}" class="sidebar-link">🧭 Menus</a>
<a href="{{ route('admin.modules') }}" class="sidebar-link">📦 Modules</a>
<a href="{{ route('admin.plugins') }}" class="sidebar-link">🔌 Plugins</a>
<a href="{{ route('admin.themes') }}" class="sidebar-link">🎨 Themes</a>
<a href="{{ route('admin.tenants') }}" class="sidebar-link">🏢 Tenants</a>
<a href="{{ route('admin.plans') }}" class="sidebar-link">💳 Plans</a>
<a href="{{ route('admin.licenses') }}" class="sidebar-link">🔑 Licenses</a>
<a href="{{ route('admin.settings') }}" class="sidebar-link">⚙️ Settings</a>
<a href="{{ route('admin.health') }}" class="sidebar-link">💚 Health</a>
<a href="{{ route('admin.audits') }}" class="sidebar-link">📜 Audit Log</a>
<a href="{{ route('admin.backups') }}" class="sidebar-link">💾 Backups</a>
</nav></aside>
<div class="flex-1 min-w-0">
<header class="sticky top-0 z-30 bg-white/80 dark:bg-slate-900/80 backdrop-blur border-b border-slate-200 dark:border-slate-800 px-4 py-3 flex items-center gap-3">
<button @click="sidebar=!sidebar" class="lg:hidden btn-primary">☰</button>
<button @click="sidebar=!sidebar" class="hidden lg:block text-slate-500">☰</button>
<form action="{{ route('admin.search') }}" class="flex-1 max-w-md"><input name="q" value="{{ request('q') }}" placeholder="Search…  ( / )" class="input"></form>
<button @click="dark=!dark;localStorage.setItem('lindu-dark',dark?'1':'0')" class="text-xl">🌙</button>
<div class="relative" x-data="{open:false}"><button @click="open=!open" class="flex items-center gap-2"><span class="w-8 h-8 rounded-full bg-indigo-100 grid place-items-center">{{ substr(auth()->user()->name??'A',0,1) }}</span><span class="text-sm hidden md:block">{{ auth()->user()->name }}</span></button>
<div x-show="open" @click.outside="open=false" class="absolute right-0 mt-2 w-48 card p-2 text-sm"><form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full text-left px-3 py-2 hover:bg-slate-100 rounded">Logout</button></form></div></div>
</header>
<main class="p-4 lg:p-6 max-w-7xl mx-auto">
@if(session('ok'))<div class="mb-4 p-3 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">{{ session('ok') }}</div>@endif
@if($errors->any())<div class="mb-4 p-3 rounded-lg bg-rose-50 text-rose-700 border border-rose-200">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
<div class="text-xs text-slate-500 mb-3"><a href="{{ route('admin.dashboard') }}">Home</a> / @yield('crumb','')</div>
@yield('content')
</main></div></div>
@stack('scripts')</body></html>
