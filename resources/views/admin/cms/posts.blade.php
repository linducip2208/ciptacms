@extends('admin.layout')
@section('title','Posts')@section('crumb','Posts')
@section('content')
<div class="card"><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Title</th><th>Category</th><th>Author</th><th>Status</th></tr></thead><tbody>@foreach($rows as $r)<tr><td>{{ $r->title }}</td><td>{{ $r->category?->name }}</td><td>{{ $r->author?->name }}</td><td><span class="badge">{{ $r->status }}</span></td></tr>@endforeach
@include('admin.partials.empty-row', [
    'count' => $rows->total(),
    'attributes' => new \Illuminate\View\ComponentAttributeBag(['colspan' => 4]),
    'icon' => 'ti-news',
    'title' => 'No posts yet',
    'message' => 'The blog has no entries in this view. Create the first post, or clear the category filter.',
    'action' => ['label' => 'Create post', 'url' => route('admin.cms.posts.create')],
])
</tbody></table></div><div class="mt-2">{{ $rows->links() }}</div></div>
@endsection
