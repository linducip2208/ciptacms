@extends('admin.layout')
@section('title', 'Forms')
@section('crumb', 'Content / Forms')

@section('content')
<div x-data="{ open: @js((bool) old('title')) }">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h2 class="mb-1">Forms</h2>
            <div class="text-muted">
                A form is a list of fields plus a submit button. Build one in the
                builder, embed it on a page with the <code>form</code> block, and
                submissions land in the submissions list.
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.cms.submissions.index') }}" class="btn btn-outline">Submissions</a>
            <button type="button" class="btn btn-primary" @click="open = !open"
                    x-text="open ? 'Close' : 'New form'"></button>
        </div>
    </div>

    @if(session('ok'))
        <div class="alert alert-ok">{{ session('ok') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-err">
            <ul class="mb-0" style="padding-left:18px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card mb-3" x-show="open" x-cloak style="display:none">
        <div class="card-header"><h3 class="card-title mb-0">New form</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.cms.forms.store') }}">
                @csrf
                <div class="row g-2">
                    <div class="col-md-6 form-group">
                        <label class="form-label" for="new-title">Title *</label>
                        <input id="new-title" name="title" class="form-control" required
                               value="{{ old('title') }}" placeholder="Contact us">
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="form-label" for="new-slug">Slug</label>
                        <input id="new-slug" name="slug" class="form-control font-monospace"
                               value="{{ old('slug') }}" placeholder="generated from the title">
                    </div>
                    <div class="col-md-8 form-group">
                        <label class="form-label" for="new-description">Description</label>
                        <input id="new-description" name="description" class="form-control"
                               value="{{ old('description') }}" placeholder="Shown above the fields">
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="form-label" for="new-submit-label">Submit button label</label>
                        <input id="new-submit-label" name="submit_label" class="form-control"
                               value="{{ old('submit_label', 'Submit') }}">
                    </div>
                    <div class="col-md-8 form-group">
                        <label class="form-label" for="new-success">Success message</label>
                        <input id="new-success" name="success_message" class="form-control"
                               value="{{ old('success_message') }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label class="form-label" for="new-status">Status</label>
                        <select id="new-status" name="status" class="form-control">
                            @foreach(['published', 'draft'] as $s)
                                <option value="{{ $s }}" @selected(old('status', 'published') === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Create and open the builder</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Fields</th>
                        <th>Submissions</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                        <tr>
                            <td><b>{{ $r->name }}</b></td>
                            <td class="font-monospace text-muted">{{ $r->slug }}</td>
                            <td>{{ $r->fields_count }}</td>
                            <td>{{ $r->submissions_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.cms.submissions.index', ['form_id' => $r->id]) }}"
                                   class="btn btn-sm btn-outline">Submissions</a>
                                <a href="{{ route('admin.cms.forms.builder', $r) }}"
                                   class="btn btn-sm btn-primary">Builder</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No forms yet. Create one, then drag fields onto its canvas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rows->hasPages())
            <div class="card-footer">{{ $rows->links() }}</div>
        @endif
    </div>
</div>
@endsection
