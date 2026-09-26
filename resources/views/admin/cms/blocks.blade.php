@extends('admin.layout')
@section('title', 'Blocks')
@section('crumb', 'Page Builder / Blocks')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Reusable blocks</h2>
        <div class="text-muted">Saved snippets the page builder can drop into any page. Global blocks are offered everywhere.</div>
    </div>
    <a href="{{ route('admin.cms.templates.index') }}" class="btn btn-outline">Templates</a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">New block</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.cms.blocks.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Name *</label>
                        <input name="name" value="{{ old('name') }}" class="form-control" required placeholder="Call to action">
                    </div>
                    <div class="form-group">
                        <label>Component type *</label>
                        <select name="type" class="form-control">
                            @foreach($catalog as $c)
                                <option value="{{ $c['type'] }}">{{ $c['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Data (JSON)</label>
                        <textarea name="data" rows="8" class="form-control font-monospace"
                                  placeholder='{"heading": "Get in touch", "link": "/contact"}'>{{ old('data') }}</textarea>
                        <small class="text-muted">Use the inspector fields listed under Components for this type.</small>
                    </div>
                    <div class="row">
                        <div class="form-group col-6">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_global" value="1">
                                <span class="form-check-label">Global</span>
                            </label>
                        </div>
                        <div class="form-group col-6">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                <span class="form-check-label">Active</span>
                            </label>
                        </div>
                    </div>
                    <button class="btn btn-primary">Save block</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Name</th><th>Type</th><th>Scope</th><th>State</th><th></th></tr></thead>
                    <tbody>
                        @forelse($rows as $b)
                            <tr>
                                <td><b>{{ $b->name }}</b><div class="text-muted text-xs">{{ $b->slug }}</div></td>
                                <td><code>{{ $b->type }}</code></td>
                                <td><span class="badge">{{ $b->is_global ? 'global' : 'local' }}</span></td>
                                <td>
                                    <span class="badge bg-{{ $b->is_active ? 'green' : 'secondary' }}">
                                        {{ $b->is_active ? 'active' : 'off' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button class="text-indigo-600" data-bs-toggle="modal" data-bs-target="#b-{{ $b->id }}">Edit</button>
                                        <form method="POST" action="{{ route('admin.cms.blocks.destroy', $b) }}"
                                              onsubmit="return confirm('Delete this block?')">@csrf @method('DELETE')
                                            <button class="text-rose-600">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <div class="modal fade" id="b-{{ $b->id }}" tabindex="-1">
                                <div class="modal-dialog"><div class="modal-content">
                                    <form method="POST" action="{{ route('admin.cms.blocks.update', $b) }}">
                                        @csrf @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ $b->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group"><label>Name</label><input name="name" value="{{ $b->name }}" class="form-control" required></div>
                                            <div class="form-group">
                                                <label>Type</label>
                                                <select name="type" class="form-control">
                                                    @foreach($catalog as $c)
                                                        <option value="{{ $c['type'] }}" @selected($b->type === $c['type'])>{{ $c['label'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Data (JSON)</label>
                                                <textarea name="data" rows="8" class="form-control font-monospace">{{ json_encode($b->data, JSON_PRETTY_PRINT) }}</textarea>
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-6">
                                                    <label class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="is_global" value="1" @checked($b->is_global)>
                                                        <span class="form-check-label">Global</span>
                                                    </label>
                                                </div>
                                                <div class="form-group col-6">
                                                    <label class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($b->is_active)>
                                                        <span class="form-check-label">Active</span>
                                                    </label>
                                                </div>
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
                            <tr><td colspan="5" class="text-center text-muted py-4">No saved blocks yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
