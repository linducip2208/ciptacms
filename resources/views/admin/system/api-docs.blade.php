@extends('admin.layout')
@section('title','API Docs')@section('crumb','API')
@section('content')
<div class="card mb-3"><div class="card-header"><h3 class="card-title">Lindu API — Tabler + Swagger</h3></div>
<div class="card-body"><p class="text-muted">Base <code>/api/v1/</code> & <code>/api/v2/</code> (v2: <code>?include=&fields=</code>). Auth Bearer Sanctum.</p>
<pre class="bg-dark text-green p-3 rounded small overflow-auto">POST /api/v1/auth/login {"email","password"} -&gt; {data:{token}}
GET  /api/v2/pages?include=category&amp;fields=id,title&amp;per_page=15  (Authorization: Bearer TOKEN)</pre>
<div class="btn-list"><a class="btn btn-primary" href="/api/docs/openapi.json" target="_blank">OpenAPI JSON</a> <a class="btn btn-outline-primary" href="/docs" target="_blank">Public docs</a></div></div></div>
<div class="card"><div class="card-header"><h3 class="card-title">Swagger UI</h3></div><div class="card-body">
@vite(['resources/js/swagger.js'])
<div id="swagger"></div>
<script>renderLinduSwagger('swagger', '/api/docs/openapi.json');</script>
</div></div>
@endsection
