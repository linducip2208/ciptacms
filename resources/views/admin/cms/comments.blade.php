@extends('admin.layout')
@section('title','Comments')@section('crumb','Comments')
@section('content')
<div class="card p-4"><table class="tbl"><thead><tr><th>On</th><th>Body</th><th>Status</th><th></th></tr></thead><tbody>@foreach($rows as $r)<tr><td>{{ $r->commentable_type }} #{{ $r->commentable_id }}</td><td>{{ Str::limit($r->body,80) }}</td><td>{{ $r->status }}</td><td class="flex gap-2">@foreach(['approved','spam','trash'] as $s)<form method="POST" action="{{ route('admin.cms.comments.moderate',[$r,$s]) }}">@csrf<button class="text-xs text-indigo-600">{{ $s }}</button></form>@endforeach</td></tr>@endforeach</tbody></table><div class="mt-2">{{ $rows->links() }}</div></div>
@endsection
