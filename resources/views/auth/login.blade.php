<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login | Lindu CMS</title>{{-- Bundled locally: a CDN outage must never lock operators out of their own admin. --}}@vite(['resources/css/tabler.css'])</head>
<body class="d-flex flex-column"><div class="page page-center"><div class="container container-tight py-4">
<div class="text-center mb-3"><span class="badge bg-blue text-white p-2">L</span> <b>{{ setting('general.site_name','Lindu CMS') }}</b></div>
<div class="card card-md"><div class="card-body">
<h2 class="h2 text-center mb-3">Login</h2>
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
@if(session('ok'))<div class="alert alert-success">{{ session('ok') }}</div>@endif
<form method="POST" action="{{ route('login.attempt') }}">@csrf
<div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" required class="form-control" value="{{ old('email') }}"></div>
<div class="mb-3"><label class="form-label">Password</label><input name="password" type="password" required class="form-control"></div>
<label class="form-check mb-3"><input type="checkbox" name="remember" class="form-check-input"> <span class="form-check-label">Remember me</span></label>
<button class="btn btn-primary w-100">Login</button></form>
<div class="text-center text-muted mt-3"><a href="{{ route('register') }}">Register</a> · <a href="{{ route('password.request') }}">Forgot?</a></div>
<div class="text-center mt-2"><a class="btn btn-outline-primary btn-sm" href="{{ route('oauth.redirect','google') }}">Google</a> <a class="btn btn-outline-dark btn-sm" href="{{ route('oauth.redirect','github') }}">GitHub</a></div>
</div></div></div></div></body></html>
