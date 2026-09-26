@extends('admin.layout')
@section('title','Settings')@section('crumb','Settings')
@section('content')
<div class="card p-5"><form method="POST" action="{{ route('admin.settings.update') }}">@csrf
@foreach($settings->groupBy('group') as $g=>$rows)<h3 class="font-bold mt-4 mb-2 capitalize">{{ $g }}</h3>@foreach($rows as $s)<div class="mb-2"><label class="text-sm">{{ $s->key }} <span class="text-slate-400">({{ $s->type }})</span></label><input name="settings[{{ $s->key }}]" value="{{ is_array($s->value)?json_encode($s->value):$s->value }}" class="input"></div>@endforeach@endforeach
<div class="grid md:grid-cols-4 gap-2 mt-4 border-t pt-4"><input name="new_key" placeholder="new.key" class="input"><input name="new_value" placeholder="value" class="input"><select name="new_type" class="input"><option>text</option><option>number</option><option>boolean</option><option>select</option><option>json</option><option>file</option><option>image</option><option>secret</option></select><input name="new_group" placeholder="group" class="input"></div>
<button class="btn-primary mt-4">Save settings</button></form></div>
@endsection
