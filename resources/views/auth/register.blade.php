<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Register | Lindu</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="min-h-screen grid place-items-center bg-slate-100"><div class="w-full max-w-sm bg-white rounded-2xl shadow p-6"><h1 class="font-bold mb-4">Create account</h1>
@if($errors->any())<div class="mb-3 text-sm text-rose-600">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
<form method="POST" action="{{ route('register.attempt') }}">@csrf
<input name="name" placeholder="Name" required class="w-full border rounded-lg px-3 py-2 mb-2"><input name="email" type="email" placeholder="Email" required class="w-full border rounded-lg px-3 py-2 mb-2">
<input name="password" type="password" placeholder="Password" required class="w-full border rounded-lg px-3 py-2 mb-2"><input name="password_confirmation" type="password" placeholder="Confirm" required class="w-full border rounded-lg px-3 py-2 mb-3">
<button class="w-full bg-indigo-600 text-white rounded-lg py-2">Register</button></form>
<div class="text-sm mt-3"><a href="{{ route('login') }}" class="text-indigo-600">Back to login</a></div></div></body></html>
