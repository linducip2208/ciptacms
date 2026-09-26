@extends('admin.layout')
@section('title', 'Templates')
@section('crumb', 'Page Builder / Templates')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Templates</h2>
        <div class="text-muted">Reusable page layouts. Applying one snapshots the target page first.</div>
    </div>
    <a href="{{ route('admin.cms.blocks.index') }}" class="btn btn-outline">Blocks</a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">New template</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.cms.templates.store') }}">
                    @csrf
                    <div class="form-group"><label>Name *</label><input name="name" value="{{ old('name') }}" class="form-control" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" rows="2" class="form-control">{{ old('description') }}</textarea></div>
                    <div class="form-group">
                        <label>Structure (JSON)</label>
                        <textarea name="structure" rows="10" class="form-control font-monospace" placeholder='{"sections": [], "blocks": []}'>{{ old('structure') }}</textarea>
                        <small class="text-muted">Same shape the page builder saves.</small>
                    </div>
                    <label class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                        <span class="form-check-label">Active</span>
                    </label>
                    <button class="btn btn-primary">Save template</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Name</th><th>Description</th><th>State</th><th>Apply to</th><th></th></tr></thead>
                    <tbody>
                        @forelse($rows as $t)
                            <tr>
                                <td><b>{{ $t->name }}</b><div class="text-muted text-xs">{{ $t->slug }}</div></td>
                                <td class="text-muted small">{{ \Illuminate\Support\Str::limit($t->description, 60) }}</td>
                                <td>
                                    <span class="badge bg-{{ $t->is_active ? 'green' : 'secondary' }}">
                                        {{ $t->is_active ? 'active' : 'off' }}
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.cms.templates.apply', $t) }}" class="d-flex gap-1">
                                        @csrf
                                        <select name="page_id" class="form-control form-control-sm" required>
                                            <option value="">Pick a page…</option>
                                            @foreach(\App\Models\Page::orderBy('title')->get() as $p)
                                                <option value="{{ $p->id }}">{{ $p->title }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-outline" onclick="return confirm('Apply this template? The current layout is snapshotted first.')">Apply</button>
                                    </form>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button class="text-indigo-600" data-bs-toggle="modal" data-bs-target="#t-{{ $t->id }}">Edit</button>
                                        <form method="POST" action="{{ route('admin.cms.templates.destroy', $t) }}"
                                              onsubmit="return confirm('Delete this template?')">@csrf @method('DELETE')
                                            <button class="text-rose-600">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <div class="modal fade" id="t-{{ $t->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg"><div class="modal-content">
                                    <form method="POST" action="{{ route('admin.cms.templates.update', $t) }}">
                                        @csrf @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ $t->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group"><label>Name</label><input name="name" value="{{ $t->name }}" class="form-control" required></div>
                                            <div class="form-group"><label>Description</label><textarea name="description" rows="2" class="form-control">{{ $t->description }}</textarea></div>
                                            <div class="form-group">
                                                <label>Structure (JSON)</label>
                                                <textarea name="structure" rows="12" class="form-control font-monospace">{{ json_encode($t->structure(), JSON_PRETTY_PRINT) }}</textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                                            <button class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
                                </div></div>
                            </div>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No templates yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
