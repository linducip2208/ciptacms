@extends('admin.layout')
@section('title', 'Company Profile')
@section('crumb', 'Company Profile')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h2 class="mb-1">Company Profile</h2>
        <div class="text-muted">Everything on the public website is managed from here.</div>
    </div>
    <a href="{{ url('/') }}" target="_blank" rel="noopener" class="btn btn-outline">View website ↗</a>
</div>

<div class="row g-3 mb-4">
    @foreach($counts as $key => $value)
        <div class="col-6 col-md-3 col-xl-2">
            <a href="{{ match($key) {
                'messages' => route('admin.company.messages'),
                'applications' => route('admin.company.applications'),
                'albums' => route('admin.company.albums'),
                default => route('admin.company.index', $key),
            } }}" class="card text-decoration-none d-block">
                <div class="card-body text-center p-3">
                    <div class="h1 mb-0">{{ $value }}</div>
                    <div class="text-muted text-uppercase" style="font-size:.7rem;letter-spacing:.04em">{{ str_replace('_', ' ', $key) }}</div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Recent contact messages</h3>
                <a href="{{ route('admin.company.messages') }}">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>From</th><th>Subject</th><th>Status</th><th>When</th></tr></thead>
                    <tbody>
                        @forelse($recentMessages as $m)
                            <tr>
                                <td><b>{{ $m->name }}</b><div class="text-muted small">{{ $m->email }}</div></td>
                                <td>{{ \Illuminate\Support\Str::limit($m->subject, 34) }}</td>
                                <td><span class="badge bg-{{ $m->status === 'new' ? 'blue' : 'secondary' }}">{{ $m->status }}</span></td>
                                <td class="text-muted small">{{ $m->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No messages yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Recent job applications</h3>
                <a href="{{ route('admin.company.applications') }}">View all</a>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Applicant</th><th>Position</th><th>Status</th><th>When</th></tr></thead>
                    <tbody>
                        @forelse($recentApplications as $a)
                            <tr>
                                <td><b>{{ $a->name }}</b><div class="text-muted small">{{ $a->email }}</div></td>
                                <td>{{ $a->career?->position ?? '—' }}</td>
                                <td><span class="badge bg-{{ $a->status === 'received' ? 'blue' : 'secondary' }}">{{ $a->status }}</span></td>
                                <td class="text-muted small">{{ $a->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No applications yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
