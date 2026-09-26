@extends('admin.layout')
@section('title', 'Redirects')
@section('crumb', 'SEO / Redirects')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">URL redirects</h2>
        <div class="text-muted">Applied by the CMS before routing. Paths are stored with a leading slash.</div>
    </div>
    <a href="{{ route('admin.seo.index') }}" class="btn btn-outline">← SEO overview</a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Add redirect</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.seo.redirects.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>From path *</label>
                        <input name="from_path" value="{{ old('from_path') }}" class="form-control" required placeholder="/old-page">
                    </div>
                    <div class="form-group">
                        <label>To path or URL *</label>
                        <input name="to_path" value="{{ old('to_path') }}" class="form-control" required placeholder="/new-page or https://…">
                    </div>
                    <div class="form-group">
                        <label>Status code</label>
                        <select name="status_code" class="form-control">
                            @foreach([301 => '301 — Permanent', 302 => '302 — Temporary', 307 => '307 — Temporary (keep method)', 308 => '308 — Permanent (keep method)'] as $v => $l)
                                <option value="{{ $v }}" @selected((int) old('status_code', 301) === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="redir-active">
                        <span class="form-check-label">Active</span>
                    </label>
                    <button class="btn btn-primary">Save redirect</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <form class="card-header" method="GET">
                <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search paths…">
            </form>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>From</th><th>To</th><th>Code</th><th>Hits</th><th>State</th><th></th></tr></thead>
                    <tbody>
                        @forelse($rows as $red)
                            <tr>
                                <td><code>{{ $red->from_path }}</code></td>
                                <td><code>{{ $red->to_path }}</code></td>
                                <td><span class="badge">{{ $red->status_code }}</span></td>
                                <td>{{ $red->hits }}</td>
                                <td>
                                    <span class="badge bg-{{ $red->is_active ? 'green' : 'secondary' }}">
                                        {{ $red->is_active ? 'active' : 'disabled' }}
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.seo.redirects.destroy', $red) }}"
                                          onsubmit="return confirm('Delete this redirect?')">@csrf @method('DELETE')
                                        <button class="text-rose-600">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No redirects yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
