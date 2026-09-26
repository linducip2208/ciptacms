@extends('admin.layout')
@section('title', 'Gallery Albums')
@section('crumb', 'Company Profile / Gallery')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Gallery Albums</h2>
        <div class="text-muted">Albums shown at <a href="{{ route('site.gallery') }}" target="_blank" rel="noopener">/gallery</a>.</div>
    </div>
    <a href="{{ route('admin.company.home') }}" class="btn btn-outline">← Company Profile</a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">New album</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.company.albums.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Title *</label>
                        <input name="title" value="{{ old('title') }}" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Cover image URL</label>
                        <input name="cover" value="{{ old('cover') }}" class="form-control" placeholder="/storage/…">
                    </div>
                    <button class="btn btn-primary">Create album</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Cover</th><th>Title</th><th>Photos</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse($rows as $album)
                            <tr>
                                <td>
                                    @if($album->cover)
                                        <img src="{{ $album->cover }}" alt="" style="width:56px;height:40px;object-fit:cover;border-radius:4px">
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td><b>{{ $album->title }}</b><div class="text-muted small">/{{ $album->slug }}</div></td>
                                <td>{{ $album->images_count }}</td>
                                <td><span class="badge bg-{{ $album->status === 'published' ? 'green' : 'secondary' }}">{{ $album->status }}</span></td>
                                <td class="d-flex gap-2">
                                    <a class="text-indigo-600" href="{{ route('admin.company.albums.images', $album) }}">Manage photos</a>
                                    <form method="POST" action="{{ route('admin.company.albums.destroy', $album) }}"
                                          onsubmit="return confirm('Delete this album?')">@csrf @method('DELETE')
                                        <button class="text-rose-600">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No albums yet. Create the first one.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
