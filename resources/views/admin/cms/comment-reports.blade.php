@extends('admin.layout')
@section('title', 'Comment Reports')
@section('crumb', 'Comments / Reports')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Reported comments</h2>
        <div class="text-muted">{{ $openCount }} open report(s).</div>
    </div>
    <a href="{{ route('admin.cms.comments.index') }}" class="btn btn-outline">← Comments</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Comment</th><th>Reporter IP</th><th>Reason</th><th>Status</th><th>When</th><th></th></tr></thead>
            <tbody>
                @forelse($rows as $rep)
                    <tr>
                        <td style="max-width:280px">
                            @if($rep->comment)
                                <div class="text-truncate-cell">{{ $rep->comment->body }}</div>
                                <div class="text-muted text-xs">
                                    by {{ $rep->comment->author_name }}
                                    @if($rep->comment->commentable)
                                        · on {{ class_basename($rep->comment->commentable_type) }} #{{ $rep->comment->commentable_id }}
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">Comment #{{ $rep->comment_id }} (deleted)</span>
                            @endif
                        </td>
                        <td class="text-muted text-xs">{{ $rep->reporter_ip ?: '—' }}</td>
                        <td class="text-muted small">{{ $rep->reason ?: '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.cms.comments.reports.status', $rep) }}">
                                @csrf
                                <select name="status" onchange="this.form.submit()" class="form-control form-control-sm">
                                    @foreach(['open' => 'Open', 'resolved' => 'Resolved', 'ignored' => 'Ignored'] as $k => $l)
                                        <option value="{{ $k }}" @selected($rep->status === $k)>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="text-muted small">{{ $rep->created_at->diffForHumans() }}</td>
                        <td>
                            @if($rep->comment)
                                <form method="POST" action="{{ route('admin.cms.comments.moderate', [$rep->comment, 'spam']) }}">
                                    @csrf
                                    <button class="text-rose-600">Mark spam</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No reports filed.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>
@endsection
