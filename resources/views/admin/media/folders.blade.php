@extends('admin.layout')
@section('title', 'Media Folders')
@section('crumb', 'Media / Folders')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Media folders</h2>
        <div class="text-muted">{{ $rows->total() }} folders · {{ $rootCount }} files in the library root</div>
    </div>
    <a href="{{ route('admin.media.index') }}" class="btn btn-outline">← Library</a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">New folder</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.media.folders.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Name *</label>
                        <input name="name" value="{{ old('name') }}" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Parent folder</label>
                        <select name="parent_id" class="form-control">
                            <option value="">— root —</option>
                            @foreach($rows as $f)
                                <option value="{{ $f->id }}" @selected((int) old('parent_id') === $f->id)>{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <input name="description" value="{{ old('description') }}" class="form-control">
                    </div>
                    <button class="btn btn-primary">Create folder</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <form class="card-header d-flex gap-2" method="GET">
                <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search folders…">
                <button class="btn btn-primary">Search</button>
            </form>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Name</th><th>Parent</th><th>Files</th><th>Size</th><th></th></tr></thead>
                    <tbody>
                        @forelse($rows as $f)
                            <tr>
                                <td>
                                    <b>{{ $f->name }}</b>
                                    @if($f->description)<div class="text-muted small">{{ $f->description }}</div>@endif
                                </td>
                                <td class="text-muted small">{{ $f->parent?->name ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('admin.media.index', ['folder_id' => $f->id]) }}">{{ $f->files_count }}</a>
                                </td>
                                <td class="text-muted small">
                                    {{ $f->files_sum_size ? number_format($f->files_sum_size / 1024, 1).' KB' : '—' }}
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button class="text-indigo-600" data-bs-toggle="modal" data-bs-target="#f-{{ $f->id }}">Edit</button>
                                        <form method="POST" action="{{ route('admin.media.folders.destroy', $f) }}"
                                              onsubmit="return confirm('Delete this folder?')">@csrf @method('DELETE')
                                            <button class="text-rose-600">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <div class="modal fade" id="f-{{ $f->id }}" tabindex="-1">
                                <div class="modal-dialog"><div class="modal-content">
                                    <form method="POST" action="{{ route('admin.media.folders.update', $f) }}">
                                        @csrf @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ $f->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group"><label>Name</label><input name="name" value="{{ $f->name }}" class="form-control" required></div>
                                            <div class="form-group">
                                                <label>Parent folder</label>
                                                <select name="parent_id" class="form-control">
                                                    <option value="">— root —</option>
                                                    @foreach($rows->where('id', '!=', $f->id) as $p)
                                                        <option value="{{ $p->id }}" @selected($f->parent_id === $p->id)>{{ $p->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group"><label>Description</label><input name="description" value="{{ $f->description }}" class="form-control"></div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                                            <button class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
                                </div></div>
                            </div>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No folders yet. Create one on the left.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
