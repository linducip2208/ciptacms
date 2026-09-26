@extends('admin.layout')
@section('title', 'Widgets')
@section('crumb', 'Appearance / Widgets')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Widgets</h2>
        <div class="text-muted">Reusable blocks placed in sidebars by the active theme.</div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Add widget</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.widgets.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Sidebar</label>
                        <input name="sidebar" value="{{ old('sidebar', 'sidebar-1') }}" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="type" class="form-control">
                            @foreach($types as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Title</label>
                        <input name="title" value="{{ old('title') }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Configuration (JSON)</label>
                        <textarea name="config" rows="5" class="form-control font-monospace" placeholder='{"limit": 5, "body": "…"}'>{{ old('config') }}</textarea>
                        <small class="text-muted">Optional. For <code>text</code>/<code>html</code> widgets a plain string is also accepted.</small>
                    </div>
                    <label class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_visible" value="1" checked>
                        <span class="form-check-label">Visible</span>
                    </label>
                    <button class="btn btn-primary">Add widget</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        @forelse($rows as $sidebar => $widgets)
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">{{ $sidebar }} ({{ $widgets->count() }})</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Title</th><th>Type</th><th>Order</th><th>Visible</th><th>Config</th><th></th></tr></thead>
                        <tbody>
                            @foreach($widgets as $w)
                                <tr>
                                    <td><b>{{ $w->title ?: '—' }}</b></td>
                                    <td><code>{{ $w->type }}</code></td>
                                    <td>{{ $w->sort_order }}</td>
                                    <td>
                                        <span class="badge bg-{{ $w->is_visible ? 'green' : 'secondary' }}">
                                            {{ $w->is_visible ? 'yes' : 'no' }}
                                        </span>
                                    </td>
                                    <td class="text-muted small" style="max-width:200px">
                                        <div class="text-truncate-cell">{{ json_encode($w->config) }}</div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button class="text-indigo-600" data-bs-toggle="modal" data-bs-target="#w-{{ $w->id }}">Edit</button>
                                            <form method="POST" action="{{ route('admin.widgets.destroy', $w) }}"
                                                  onsubmit="return confirm('Remove this widget?')">@csrf @method('DELETE')
                                                <button class="text-rose-600">Remove</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <div class="modal fade" id="w-{{ $w->id }}" tabindex="-1">
                                    <div class="modal-dialog"><div class="modal-content">
                                        <form method="POST" action="{{ route('admin.widgets.update', $w) }}">
                                            @csrf @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit widget</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label>Title</label>
                                                    <input name="title" value="{{ $w->title }}" class="form-control">
                                                </div>
                                                <div class="form-group">
                                                    <label>Configuration (JSON)</label>
                                                    <textarea name="config" rows="5" class="form-control font-monospace">{{ json_encode($w->config, JSON_PRETTY_PRINT) }}</textarea>
                                                </div>
                                                <label class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="is_visible" value="1" @checked($w->is_visible)>
                                                    <span class="form-check-label">Visible</span>
                                                </label>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                                                <button class="btn btn-primary">Save</button>
                                            </div>
                                        </form>
                                    </div></div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    No widgets yet. Add one with the form on the left.
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
