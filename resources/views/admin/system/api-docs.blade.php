@extends('admin.layout')
@section('title','API Docs')@section('crumb','API')
@section('content')
<div class="card p-5"><h2 class="font-bold">Lindu API</h2>
<p class="text-sm">Base <code>/api/v1/</code> & <code>/api/v2/</code> (v2 tambah <code>?include=</code> + <code>?fields=</code>). Auth: Bearer Sanctum.</p>
<pre class="text-xs bg-slate-900 text-emerald-200 rounded p-3 mt-2 overflow-auto">POST /api/v1/auth/login {"email","password"} -> {data:{token}}
GET  /api/v2/pages?include=category&fields=id,title&per_page=15  (Header: Authorization: Bearer TOKEN)
GET  /api/docs/openapi.json</pre>
<a class="btn-primary mt-2 inline-block" href="/api/docs/openapi.json" target="_blank">OpenAPI JSON</a></div>
@endsection
