@extends('admin.layout')
@section('title', 'Album: '.$album->title)
@section('crumb', 'Company Profile / Gallery / '.$album->title)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">{{ $album->title }}</h2>
        <div class="text-muted">
            <a href="{{ route('admin.company.albums') }}">← All albums</a> ·
            <a href="{{ route('site.gallery.album', $album->slug) }}" target="_blank" rel="noopener">View on site ↗</a>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Add photo</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.company.albums.images.store', $album) }}">
                    @csrf
                    <div class="form-group">
                        <label>Image path / URL *</label>
                        <input name="path" value="{{ old('path') }}" class="form-control" required placeholder="/storage/media/…">
                        <small class="text-muted">Upload in the <a href="{{ route('admin.media.index') }}" target="_blank">Media Library</a> first, then paste the path.</small>
                    </div>
                    <div class="form-group">
                        <label>Caption</label>
                        <input name="caption" value="{{ old('caption') }}" class="form-control">
                    </div>
                    <button class="btn btn-primary">Add photo</button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Album settings</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.company.albums.update', $album) }}">
                    @csrf @method('PUT')
                    <div class="form-group">
                        <label>Title</label>
                        <input name="title" value="{{ $album->title }}" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3" class="form-control">{{ $album->description }}</textarea>
                    </div>
                    <div class="row">
                        <div class="form-group col-7">
                            <label>Cover URL</label>
                            <input name="cover" value="{{ $album->cover }}" class="form-control">
                        </div>
                        <div class="form-group col-5">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="published" @selected($album->status === 'published')>Published</option>
                                <option value="draft" @selected($album->status === 'draft')>Draft</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary">Save album</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Photos ({{ $images->count() }})</h3></div>
            <div class="card-body">
                <div class="row g-2" id="album-images">
                    @forelse($images as $img)
                        <div class="col-6 col-md-4" data-id="{{ $img->id }}">
                            <div class="card p-2" style="border:1px solid #e6e9f2">
                                <img src="{{ $img->path }}" alt="{{ $img->caption }}" style="width:100%;height:120px;object-fit:cover;border-radius:6px">
                                <input name="caption[]" value="{{ $img->caption }}" class="form-control form-control-sm mt-2" placeholder="Caption">
                                <form method="POST" action="{{ route('admin.company.albums.images.destroy', $img) }}"
                                      onsubmit="return confirm('Remove this photo?')" class="mt-1">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline w-100 text-rose-600">Remove</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center text-muted py-4">No photos in this album yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
