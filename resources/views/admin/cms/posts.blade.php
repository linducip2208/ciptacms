@extends('admin.layout')
@section('title','Posts')@section('crumb','Posts')
@section('content')
<div class="card p-4"><table class="tbl"><thead><tr><th>Title</th><th>Category</th><th>Author</th><th>Status</th></tr></thead><tbody>@foreach($rows as $r)<tr><td>{{ $r->title }}</td><td>{{ $r->category?->name }}</td><td>{{ $r->author?->name }}</td><td><span class="badge">{{ $r->status }}</span></td></tr>@endforeach</tbody></table><div class="mt-2">{{ $rows->links() }}</div></div>
@endsection
