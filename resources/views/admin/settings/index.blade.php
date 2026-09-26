@extends('admin.layout')
@section('title','Settings')@section('crumb','Settings')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Settings</h3></div><div class="card-body"><form method="POST" action="{{ route('admin.settings.update') }}">@csrf
@foreach($settings->groupBy('group') as $g=>$rows)<h3 class="mt-3 mb-2 text-capitalize">{{ $g }}</h3><div class="row g-2">@foreach($rows as $s)<div class="col-md-6"><label class="form-label">{{ $s->key }} <span class="text-muted">({{ $s->type }})</span></label><input name="settings[{{ $s->key }}]" value="{{ is_array($s->value)?json_encode($s->value):$s->value }}" class="form-control"></div>@endforeach</div>@endforeach
<div class="row g-2 mt-3 pt-3 border-top"><div class="col-md-3"><input name="new_key" placeholder="new.key" class="form-control"></div><div class="col-md-3"><input name="new_value" placeholder="value" class="form-control"></div><div class="col-md-3"><select name="new_type" class="form-control"><option>text</option><option>number</option><option>boolean</option><option>select</option><option>json</option><option>file</option><option>image</option><option>secret</option></select></div><div class="col-md-3"><input name="new_group" placeholder="group" class="form-control"></div></div>
<button class="btn btn-primary mt-3">Save settings</button></form></div></div>
@endsection
