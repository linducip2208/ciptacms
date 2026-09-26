@extends('admin.layout')
@section('title', ($config['label']??$resource).' form')@section('crumb', $config['label']??$resource)
@section('content')
<div class="card col-lg-8"><div class="card-header"><h3 class="card-title">Form {{ $config['label']??$resource }}</h3></div>
<div class="card-body"><form method="POST" action="{{ $row?route('admin.resource.update',[$resource,$row->id]):route('admin.resource.store',$resource) }}">@csrf @if($row)@method('PUT')@endif
@php $data=$row?->toArray()??[]; $skip=['id','created_at','updated_at','deleted_at']; @endphp
@foreach(array_keys($data) as $f) @continue(in_array($f,$skip))
<div class="mb-3"><label class="form-label">{{ $f }}</label>
@if(Str::contains($f,['description','body','bio','content','notes']))<textarea name="{{ $f }}" class="form-control" rows="4">{{ old($f,$data[$f]??'') }}</textarea>
@else<input name="{{ $f }}" value="{{ old($f, is_array($data[$f]??'')?'':($data[$f]??'')) }}" class="form-control">@endif</div>
@endforeach
@if(!$row)
<div class="mb-3"><label class="form-label">name / title</label><input name="name" class="form-control" required></div>
<div class="mb-3"><label class="form-label">slug (optional)</label><input name="slug" class="form-control"></div>
@endif
<button class="btn btn-primary">Save</button> <a href="{{ route('admin.resource.index',$resource) }}" class="btn btn-link">Cancel</a>
</form></div></div>
@endsection
