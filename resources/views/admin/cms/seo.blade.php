@extends('admin.layout')
@section('title','SEO')@section('crumb','SEO')
@section('content')
<div class="card"><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Type</th><th>Title</th><th>Description</th></tr></thead><tbody>@foreach($rows as $r)<tr><td class="text-xs">{{ $r->seoable_type }}</td><td>{{ $r->meta_title }}</td><td>{{ Str::limit($r->meta_description,80) }}</td></tr>@endforeach</tbody></table></div><div class="mt-2">{{ $rows->links() }}</div></div>
@endsection
