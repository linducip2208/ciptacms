@extends('admin.layout')
@section('title', 'Job Applications')
@section('crumb', 'Company Profile / Applications')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form class="d-flex gap-2" method="GET">
        <select name="status" class="form-control" onchange="this.form.submit()">
            <option value="">All status</option>
            @foreach(['received' => 'Received', 'reviewing' => 'Reviewing', 'shortlisted' => 'Shortlisted', 'rejected' => 'Rejected', 'hired' => 'Hired'] as $v => $l)
                <option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>
    <a href="{{ route('admin.company.index', 'careers') }}" class="btn btn-outline">Manage positions</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Applicant</th><th>Position</th><th>Cover letter</th><th>CV</th><th>Status</th><th>Applied</th></tr></thead>
            <tbody>
                @forelse($rows as $a)
                    <tr>
                        <td>
                            <b>{{ $a->name }}</b>
                            <div class="text-muted small">
                                <a href="mailto:{{ $a->email }}">{{ $a->email }}</a>
                                @if($a->phone) · {{ $a->phone }} @endif
                            </div>
                        </td>
                        <td>{{ $a->career?->position ?? '—' }}</td>
                        <td style="max-width:280px">
                            @if($a->cover_letter)
                                <details><summary class="text-indigo-600" style="cursor:pointer">Read</summary>
                                    <div class="mt-2 text-muted small" style="white-space:pre-wrap">{{ $a->cover_letter }}</div>
                                </details>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($a->cv_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($a->cv_path))
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($a->cv_path) }}" target="_blank" rel="noopener">Download</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.company.applications.status', $a) }}">
                                @csrf
                                <select name="status" onchange="this.form.submit()" class="form-control form-control-sm">
                                    @foreach(['received' => 'Received', 'reviewing' => 'Reviewing', 'shortlisted' => 'Shortlisted', 'rejected' => 'Rejected', 'hired' => 'Hired'] as $v => $l)
                                        <option value="{{ $v }}" @selected($a->status === $v)>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="text-muted small">{{ $a->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No applications yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>
@endsection
