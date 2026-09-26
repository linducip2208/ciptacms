@extends('admin.layout')
@section('title','Info')@section('crumb','Info')
@section('content')
<div class="card p-4"><pre class="text-xs">{{ json_encode($info, JSON_PRETTY_PRINT) }}</pre></div>
@endsection
