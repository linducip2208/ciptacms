@extends('admin.layout')
@section('title','Comments')@section('crumb','Comments')
@section('content')
<div class="card"><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>On</th><th>Body</th><th>Status</th><th></th></tr></thead><tbody>@foreach($rows as $r)<tr><td>{{ $r->commentable_type }} #{{ $r->commentable_id }}</td><td>{{ Str::limit($r->body,80) }}</td><td>{{ $r->status }}</td><td class="d-flex gap-2">@foreach(['approved','spam','trash'] as $s)<form method="POST" action="{{ route('admin.cms.comments.moderate',[$r,$s]) }}">@csrf<button class="text-xs text-indigo-600">{{ $s }}</button></form>@endforeach</td></tr>@endforeach
@include('admin.partials.empty-row', [
    'count' => $rows->total(),
    'attributes' => new \Illuminate\View\ComponentAttributeBag(['colspan' => 4]),
    'icon' => 'ti-messages',
    'title' => 'No comments to moderate',
    'message' => 'Nothing is waiting on you. Comments land here as soon as visitors post them.',
])
</tbody></table></div><div class="mt-2">{{ $rows->links() }}</div></div>
@endsection
