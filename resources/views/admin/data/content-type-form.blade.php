@extends('admin.layout')
@section('title', ($row->exists ? 'Edit' : 'New').' Content Type')
@section('crumb', 'Data / Content Types / '.($row->exists ? 'Edit' : 'New'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('admin.cms.types.index') }}" class="text-muted">← All content types</a>
    @if($row->exists)
        <a href="{{ route('admin.cms.records.list', $row) }}" class="btn btn-outline">View records ({{ $row->records()->count() }})</a>
    @endif
</div>

<form method="POST" action="{{ $row->exists ? route('admin.cms.types.update', $row) : route('admin.cms.types.store') }}">
    @csrf
    @if($row->exists) @method('PUT') @endif
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Name *</label>
                    <input name="name" value="{{ old('name', $row->name) }}" class="form-control" required placeholder="Product">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Slug *</label>
                    <input name="slug" value="{{ old('slug', $row->slug) }}" class="form-control" required placeholder="product">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Icon</label>
                    <input name="icon" value="{{ old('icon', $row->icon) }}" class="form-control" placeholder="📦">
                </div>
                <div class="col-md-9">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="2" class="form-control">{{ old('description', $row->description) }}</textarea>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <label class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_api_enabled" value="1"
                               @checked(old('is_api_enabled', $row->is_api_enabled ?? true))>
                        <span class="form-check-label">Expose via API</span>
                    </label>
                </div>
            </div>
        </div>
        <div class="card-footer text-right">
            <button class="btn btn-primary">{{ $row->exists ? 'Save content type' : 'Create content type' }}</button>
        </div>
    </div>
</form>

@if($row->exists)
    <div class="card">
        <div class="card-header"><h3 class="card-title">Fields ({{ count($row->fields ?? []) }})</h3></div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr><th>Label</th><th>Column</th><th>Type</th><th>Rules</th><th></th></tr></thead>
                <tbody>
                    @forelse($row->fields ?? [] as $i => $f)
                        <tr>
                            <td><b>{{ $f['name'] ?? $f['label'] }}</b></td>
                            <td><code>{{ $f['slug'] }}</code></td>
                            <td><span class="badge">{{ $fieldTypes[$f['type']]['label'] ?? $f['type'] }}</span></td>
                            <td class="text-muted small">
                                @if($f['required'] ?? false)<span class="badge bg-red">required</span>@endif
                                @if($f['unique'] ?? false)<span class="badge bg-orange">unique</span>@endif
                                @if(!empty($f['options']))<code class="text-xs">{{ implode(', ', array_slice(array_keys($f['options']), 0, 4)) }}</code>@endif
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <button class="text-indigo-600" data-bs-toggle="modal" data-bs-target="#f-{{ $i }}">Edit</button>
                                    <form method="POST" action="{{ route('admin.cms.types.fields.destroy', [$row, $i]) }}"
                                          onsubmit="return confirm('Remove this field? Existing values stay in the JSON record.')">
                                        @csrf @method('DELETE')<button class="text-rose-600">Remove</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <div class="modal fade" id="f-{{ $i }}" tabindex="-1">
                            <div class="modal-dialog"><div class="modal-content">
                                <form method="POST" action="{{ route('admin.cms.types.fields.update', [$row, $i]) }}">
                                    @csrf @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit {{ $f['name'] }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="form-group col-6"><label>Label</label><input name="name" value="{{ $f['name'] ?? '' }}" class="form-control" required></div>
                                            <div class="form-group col-6"><label>Column</label><input name="slug" value="{{ $f['slug'] }}" class="form-control" required></div>
                                        </div>
                                        <div class="form-group">
                                            <label>Type</label>
                                            <select name="type" class="form-control">
                                                @foreach($fieldTypes as $k => $meta)
                                                    <option value="{{ $k }}" @selected(($f['type'] ?? '') === $k)>{{ $meta['label'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Options</label>
                                            <textarea name="options" rows="3" class="form-control font-monospace">{{ is_array($f['options'] ?? null) ? collect($f['options'])->map(fn($v, $k) => is_int($k) ? $v : $k.'|'.$v)->implode("\n") : '' }}</textarea>
                                            <small class="text-muted">One per line, or <code>key|label</code> pairs.</small>
                                        </div>
                                        <div class="row">
                                            <div class="form-group col-6">
                                                <label class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="required" value="1" @checked($f['required'] ?? false)>
                                                    <span class="form-check-label">Required</span>
                                                </label>
                                            </div>
                                            <div class="form-group col-6">
                                                <label class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" name="unique" value="1" @checked($f['unique'] ?? false)>
                                                    <span class="form-check-label">Unique</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group"><label>Help text</label><input name="help" value="{{ $f['help'] ?? '' }}" class="form-control"></div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                                        <button class="btn btn-primary">Save field</button>
                                    </div>
                                </form>
                            </div></div>
                        </div>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No fields yet. Add one with the form below.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body border-top">
            <h4>Add field</h4>
            <form method="POST" action="{{ route('admin.cms.types.fields.store', $row) }}">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Label *</label>
                        <input name="name" class="form-control" required placeholder="Price">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Column *</label>
                        <input name="slug" class="form-control" required placeholder="price">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-control">
                            @foreach($fieldTypes as $k => $meta)
                                <option value="{{ $k }}">{{ $meta['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Options</label>
                        <input name="options" class="form-control" placeholder="a|b|c">
                    </div>
                    <div class="col-md-2 d-flex gap-3">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="required" value="1">
                            <span class="form-check-label">Req</span>
                        </label>
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="unique" value="1">
                            <span class="form-check-label">Uniq</span>
                        </label>
                    </div>
                </div>
                <button class="btn btn-primary mt-3">Add field</button>
            </form>
        </div>
    </div>
@endif
@endsection
