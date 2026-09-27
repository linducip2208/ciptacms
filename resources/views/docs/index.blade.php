<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Lindu API Docs</title>{{-- Bundled locally: the public API reference must not depend on a CDN. --}}@vite(['resources/css/app.css', 'resources/js/swagger.js'])</head>
<body><div class="page"><header class="navbar"><div class="container-xl"><h1 class="navbar-brand">Lindu CMS API</h1><div class="ms-auto"><a class="btn btn-primary" href="/admin/api-docs">Admin</a></div></div></header>
<div class="page-wrapper"><div class="page-body"><div class="container-xl"><div id="swagger"></div></div></div></div></div>
<script>renderLinduSwagger('swagger', '/api/docs/openapi.json');</script></body></html>
