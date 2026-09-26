@extends('admin.layout')
@section('title', 'Permissions')
@section('crumb', 'Users / Permissions')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form class="d-flex gap-2" method="GET">
        <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search slug, name or module…" style="min-width:240px">
        <select name="module" class="form-control" onchange="this.form.submit()">
            <option value="">All modules</option>
            @foreach($modules as $m)
                <option value="{{ $m }}" @selected(request('module') === $m)>{{ $m }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>
    <a href="{{ route('admin.permissions.groups') }}" class="btn btn-outline">Manage groups</a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">New permission</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.permissions.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Name *</label>
                        <input name="name" value="{{ old('name') }}" class="form-control" required placeholder="View pages">
                    </div>
                    <div class="form-group">
                        <label>Slug *</label>
                        <input name="slug" value="{{ old('slug') }}" class="form-control" required placeholder="pages.view">
                        <small class="text-muted">Dotted form <code>resource.action</code>. Used by policies, menus and the API.</small>
                    </div>
                    <div class="row">
                        <div class="form-group col-6">
                            <label>Action</label>
                            <select name="action" class="form-control">
                                @foreach(['view', 'create', 'read', 'update', 'delete', 'publish', 'approve', 'export', 'import', 'manage', 'configure'] as $a)
                                    <option value="{{ $a }}">{{ $a }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-6">
                            <label>Module</label>
                            <input name="module" value="{{ old('module') }}" class="form-control" placeholder="core">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Group</label>
                        <select name="group_id" class="form-control">
                            <option value="">—</option>
                            @foreach($groups as $g)
                                <option value="{{ $g->id }}" @selected((int) old('group_id') === $g->id)>{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-primary">Create permission</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Slug</th><th>Name</th><th>Action</th><th>Module</th><th>Group</th><th>Roles</th><th></th></tr></thead>
                    <tbody>
                        @forelse($rows as $p)
                            <tr>
                                <td><code>{{ $p->slug }}</code></td>
                                <td>{{ $p->name }}</td>
                                <td><span class="badge">{{ $p->action }}</span></td>
                                <td>{{ $p->module ?: '—' }}</td>
                                <td>{{ $p->group?->name ?: '—' }}</td>
                                <td>{{ $p->roles()->count() }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button class="text-indigo-600" data-bs-toggle="modal" data-bs-target="#edit-{{ $p->id }}">Edit</button>
                                        <form method="POST" action="{{ route('admin.permissions.destroy', $p) }}"
                                              onsubmit="return confirm('Delete this permission?')">@csrf @method('DELETE')
                                            <button class="text-rose-600">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <div class="modal fade" id="edit-{{ $p->id }}" tabindex="-1">
                                <div class="modal-dialog"><div class="modal-content">
                                    <form method="POST" action="{{ route('admin.permissions.update', $p) }}">
                                        @csrf @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit {{ $p->slug }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group"><label>Name</label><input name="name" value="{{ $p->name }}" class="form-control" required></div>
                                            <div class="form-group"><label>Slug</label><input name="slug" value="{{ $p->slug }}" class="form-control" required></div>
                                            <div class="row">
                                                <div class="form-group col-4">
                                                    <label>Action</label>
                                                    <select name="action" class="form-control">
                                                        @foreach(['view', 'create', 'read', 'update', 'delete', 'publish', 'approve', 'export', 'import', 'manage', 'configure'] as $a)
                                                            <option value="{{ $a }}" @selected($p->action === $a)>{{ $a }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="form-group col-4">
                                                    <label>Module</label>
                                                    <input name="module" value="{{ $p->module }}" class="form-control">
                                                </div>
                                                <div class="form-group col-4">
                                                    <label>Group</label>
                                                    <select name="group_id" class="form-control">
                                                        <option value="">—</option>
                                                        @foreach($groups as $g)
                                                            <option value="{{ $g->id }}" @selected($p->group_id === $g->id)>{{ $g->name }}</option>
                                                        @endforeach
                                                    </select>
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
                            <tr><td colspan="7" class="text-center text-muted py-4">No permissions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
