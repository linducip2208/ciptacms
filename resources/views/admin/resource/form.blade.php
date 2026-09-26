@extends('admin.layout')
@section('title', ($config['label']??$resource).' form')@section('crumb', $config['label']??$resource)
@section('content')
<div class="card p-5 max-w-3xl">
<form method="POST" action="{{ $row?route('admin.resource.update',[$resource,$row->id]):route('admin.resource.store',$resource) }}">@csrf @if($row)@method('PUT')@endif
@php $data=$row?->toArray()??[]; $skip=['id','created_at','updated_at','deleted_at']; @endphp
@foreach(array_keys($data) as $f) @continue(in_array($f,$skip))
<div class="mb-3"><label class="text-sm font-medium">{{ $f }}</label>
@if(Str::contains($f,['description','body','bio','content','notes']))<textarea name="{{ $f }}" class="input" rows="4">{{ old($f,$data[$f]??'') }}</textarea>
@else<input name="{{ $f }}" value="{{ old($f, is_array($data[$f]??'')?'':($data[$f]??'')) }}" class="input">@endif</div>
@endforeach
@if(!$row)
<div class="mb-3"><label class="text-sm font-medium">name / title</label><input name="name" class="input" required></div>
<div class="mb-3"><label class="text-sm font-medium">slug (optional)</label><input name="slug" class="input"></div>
@endif
<button class="btn-primary">Save</button> <a href="{{ route('admin.resource.index',$resource) }}" class="text-sm text-slate-500 ml-2">Cancel</a>
</form></div>
@endsection
