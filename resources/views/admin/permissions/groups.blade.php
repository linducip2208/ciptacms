@extends('admin.layout')
@section('title', 'Permission Groups')
@section('crumb', 'Users / Permissions / Groups')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Permission groups</h2>
        <div class="text-muted">Organise permissions into panels in the role editor.</div>
    </div>
    <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline">← All permissions</a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">New group</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.permissions.groups.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Name *</label>
                        <input name="name" value="{{ old('name') }}" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Slug *</label>
                        <input name="slug" value="{{ old('slug') }}" class="form-control" required placeholder="content">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="2" class="form-control">{{ old('description') }}</textarea>
                    </div>
                    <button class="btn btn-primary">Create group</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Name</th><th>Slug</th><th>Permissions</th><th>Description</th><th></th></tr></thead>
                    <tbody>
                        @forelse($rows as $g)
                            <tr>
                                <td><b>{{ $g->name }}</b></td>
                                <td><code>{{ $g->slug }}</code></td>
                                <td><span class="badge">{{ $g->permissions_count }}</span></td>
                                <td class="text-muted small">{{ \Illuminate\Support\Str::limit($g->description, 60) }}</td>
                                <td>
                                    <div class="d-flex gap-2 align-items-center">
                                        <button class="text-indigo-600" data-bs-toggle="modal" data-bs-target="#g-{{ $g->id }}">Edit</button>
                                        <form method="POST" action="{{ route('admin.permissions.groups.destroy', $g) }}"
                                              onsubmit="return confirm('Delete this group?')">@csrf @method('DELETE')
                                            <button class="text-rose-600">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <div class="modal fade" id="g-{{ $g->id }}" tabindex="-1">
                                <div class="modal-dialog"><div class="modal-content">
                                    <form method="POST" action="{{ route('admin.permissions.groups.update', $g) }}">
                                        @csrf @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit {{ $g->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group"><label>Name</label><input name="name" value="{{ $g->name }}" class="form-control" required></div>
                                            <div class="form-group"><label>Slug</label><input name="slug" value="{{ $g->slug }}" class="form-control" required></div>
                                            <div class="form-group"><label>Description</label><textarea name="description" rows="2" class="form-control">{{ $g->description }}</textarea></div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                                            <button class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
                                </div></div>
                            </div>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No groups yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
