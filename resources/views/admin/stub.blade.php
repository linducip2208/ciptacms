@extends('admin.layout')
@section('title',$title??'Module')@section('crumb',$title??'')
@section('content')<div class="card p-10 text-center"><h2 class="text-xl font-bold mb-2">{{ $title??'Module' }}</h2><p class="text-slate-500">{{ $msg??'' }}</p></div>@endsection
