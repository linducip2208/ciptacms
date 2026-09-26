@extends('admin.layout')
@section('title',$title??'Module')@section('crumb',$title??'')
@section('content')<div class="card"><div class="card-body py-5 text-center"><h2 class="card-title mb-2">{{ $title??'Module' }}</h2><p class="text-muted">{{ $msg??'' }}</p></div></div>@endsection
