@extends('admin.layout')
@section('title','Themes')@section('crumb','Themes')
@section('content')
<div class="row row-cards">@foreach($themes as $t)<div class="card"><b>{{ $t->name }}</b><div class="text-muted small">{{ $t->slug }} · v{{ $t->version }}</div><p class="text-sm my-2">{{ $t->description }}</p>@if(!$t->is_active)<form method="POST" action="{{ route('admin.themes.activate',$t->slug) }}">@csrf<button class="btn btn-primary">Activate</button></form>@else<span class="badge">active</span>@endif</div>@endforeach</div>
<div class="card mt-4"><h3 class="font-semibold mb-2">Theme settings / Custom CSS & JS</h3><form method="POST" action="{{ route('admin.themes.settings') }}">@csrf<input name="theme[custom_css]" class="form-control mb-2" placeholder="Custom CSS URL or code"><input name="theme[custom_js]" class="form-control mb-2" placeholder="Custom JS"><button class="btn btn-primary">Save</button></form></div>
@endsection
