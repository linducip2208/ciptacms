@extends('admin.layout')
@section('title','Pages')@section('crumb','Pages')
@section('content')
<div class="card"><div class="d-flex justify-content-between mb-3"><form class="d-flex gap-2"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search…"><button class="btn btn-primary">Go</button></form><a href="{{ route('admin.cms.pages.create') }}" class="btn btn-primary">+ Page</a></div>
<div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Title</th><th>Slug</th><th>Status</th><th>Published</th><th></th></tr></thead><tbody>@foreach($rows as $r)<tr><td>{{ $r->title }}</td><td>{{ $r->slug }}</td><td><span class="badge">{{ $r->status }}</span></td><td>{{ $r->published_at }}</td><td><a class="text-indigo-600" href="{{ route('admin.cms.pages.edit',$r) }}">Edit</a></td></tr>@endforeach
@include('admin.partials.empty-row', [
    'count' => $rows->total(),
    'attributes' => new \Illuminate\View\ComponentAttributeBag(['colspan' => 5]),
    'icon' => 'ti-file-text',
    'title' => 'No pages yet',
    'message' => 'Nothing published yet. Create the first page, or clear the search box if you are filtering.',
    'action' => ['label' => 'Create page', 'url' => route('admin.cms.pages.create')],
])
</tbody></table></div><div class="mt-2">{{ $rows->links() }}</div></div>
@endsection
