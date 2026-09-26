@extends('admin.layout')
@section('title','Search')@section('crumb','Search')
@section('content')
<div class="card p-4"><form><input name="q" value="{{ $q }}" class="input" placeholder="Global search…"></form><pre class="text-xs mt-3">{{ json_encode($results, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre></div>
@endsection
