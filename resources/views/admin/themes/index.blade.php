@extends('admin.layout')
@section('title','Themes')@section('crumb','Themes')
@section('content')
<div class="grid md:grid-cols-3 gap-4">@foreach($themes as $t)<div class="card p-4"><b>{{ $t->name }}</b><div class="text-xs text-slate-500">{{ $t->slug }} · v{{ $t->version }}</div><p class="text-sm my-2">{{ $t->description }}</p>@if(!$t->is_active)<form method="POST" action="{{ route('admin.themes.activate',$t->slug) }}">@csrf<button class="btn-primary">Activate</button></form>@else<span class="badge">active</span>@endif</div>@endforeach</div>
<div class="card p-4 mt-4"><h3 class="font-semibold mb-2">Theme settings / Custom CSS & JS</h3><form method="POST" action="{{ route('admin.themes.settings') }}">@csrf<input name="theme[custom_css]" class="input mb-2" placeholder="Custom CSS URL or code"><input name="theme[custom_js]" class="input mb-2" placeholder="Custom JS"><button class="btn-primary">Save</button></form></div>
@endsection
