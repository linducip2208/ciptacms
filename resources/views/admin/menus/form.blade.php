@extends('admin.layout')
@section('title', $item->exists ? 'Edit menu item' : 'New menu item')
@section('crumb', 'Appearance / Menus / '.($item->exists ? 'Edit' : 'New'))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('admin.menus.index', ['location' => $item->location]) }}" class="text-muted">← Back to menu</a>
    <nav class="d-flex gap-2">
        @foreach($locations as $loc)
            <a href="{{ route('admin.menus.index', ['location' => $loc]) }}"
               class="btn btn-sm {{ $loc === $item->location ? 'btn-primary' : 'btn-outline' }}">{{ ucfirst($loc) }}</a>
        @endforeach
    </nav>
</div>

<form method="POST" action="{{ $item->exists ? route('admin.menus.update', $item) : route('admin.menus.store') }}">
    @csrf
    @if($item->exists) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label" for="m-title">Title *</label>
                            <input id="m-title" name="title" value="{{ old('title', $item->title) }}" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="m-icon">Icon</label>
                            <input id="m-icon" name="icon" value="{{ old('icon', $item->icon) }}" class="form-control" placeholder="ti ti-home">
                            <small class="text-muted">Tabler icon class, e.g. <code>ti ti-file</code></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="m-url">URL</label>
                            <input id="m-url" name="url" value="{{ old('url', $item->url) }}" class="form-control" placeholder="/admin/cms/pages or https://…">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="m-route">Named route</label>
                            <input id="m-route" name="route" value="{{ old('route', $item->route) }}" class="form-control" placeholder="admin.cms.pages.index">
                            <small class="text-muted">Used when no URL is given.</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="m-location">Location *</label>
                            <select id="m-location" name="location" class="form-control" required>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc }}" @selected(old('location', $item->location) === $loc)>{{ ucfirst($loc) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="m-parent">Parent</label>
                            <select id="m-parent" name="parent_id" class="form-control">
                                <option value="">— top level —</option>
                                @foreach($parents as $p)
                                    <option value="{{ $p->id }}" @selected((int) old('parent_id', $item->parent_id) === $p->id)>{{ $p->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="m-order">Sort order</label>
                            <input id="m-order" type="number" name="sort_order" value="{{ old('sort_order', $item->sort_order ?? 0) }}" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="m-permission">Required permission</label>
                            <input id="m-permission" name="permission" value="{{ old('permission', $item->permission) }}" class="form-control" placeholder="pages.view">
                            <small class="text-muted">Hidden unless the user holds it.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="m-target">Target</label>
                            <select id="m-target" name="target" class="form-control">
                                @foreach(['_self' => 'Same tab', '_blank' => 'New tab'] as $k => $l)
                                    <option value="{{ $k }}" @selected(old('target', $item->target ?? '_self') === $k)>{{ $l }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="m-badge">Badge</label>
                            <input id="m-badge" name="badge" value="{{ old('badge', $item->badge) }}" class="form-control" placeholder="NEW">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="m-badge-color">Badge colour</label>
                            <select id="m-badge-color" name="badge_color" class="form-control">
                                @foreach(['' => 'default', 'blue' => 'blue', 'green' => 'green', 'red' => 'red', 'orange' => 'orange'] as $k => $l)
                                    <option value="{{ $k }}" @selected(old('badge_color', $item->badge_color) === $k)>{{ $l ?: 'default' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <label class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="is_visible" value="1"
                               @checked(old('is_visible', $item->is_visible ?? true))>
                        <span class="form-check-label">Visible</span>
                    </label>
                    <div>
                        @if($item->exists)
                            <span class="text-muted small me-3">Created {{ optional($item->created_at)->diffForHumans() }}</span>
                        @endif
                        <button class="btn btn-primary">{{ $item->exists ? 'Save menu item' : 'Create menu item' }}</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Where this appears</h3></div>
                <div class="card-body">
                    <dl class="row mb-0" style="font-size:.9rem">
                        <dt class="col-4">Location</dt>
                        <dd class="col-8"><code>{{ $item->location }}</code></dd>
                        <dt class="col-4">Children</dt>
                        <dd class="col-8">{{ $item->exists ? $item->children()->count() : 0 }}</dd>
                        <dt class="col-4">Module</dt>
                        <dd class="col-8">{{ $item->module ?: 'core' }}</dd>
                    </dl>
                    <hr>
                    <p class="text-muted small mb-0">
                        <strong>admin</strong> — the left sidebar, rendered recursively from
                        <code>menu_items</code>.<br>
                        <strong>primary</strong> — the public site header navigation.<br>
                        <strong>footer</strong> — reserved for theme footers.
                    </p>
                    <p class="text-muted small mt-2 mb-0">
                        Nesting is unlimited. A module can register its own entries from
                        <code>module.json</code>.
                    </p>
                </div>
                @if($item->exists)
                    <div class="card-footer">
                        <form method="POST" action="{{ route('admin.menus.destroy', $item) }}"
                              onsubmit="return confirm('Delete this menu item?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline w-100 text-rose-600">Delete menu item</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</form>
@endsection
