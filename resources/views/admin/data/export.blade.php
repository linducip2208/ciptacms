@extends('admin.layout')
@section('title', 'Export')
@section('crumb', 'Data / Export')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Export records</h2>
        <div class="text-muted">Download any content type as CSV or JSON. Exports round-trip back through Import.</div>
    </div>
    <a href="{{ route('admin.cms.import') }}" class="btn btn-outline">Import instead</a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Export options</h3></div>
            <div class="card-body">
                @if($types->isEmpty())
                    <p class="text-muted">No content types defined yet.</p>
                @else
                    <form method="GET" action="{{ route('admin.cms.export.run') }}">
                        <div class="form-group">
                            <label>Content type *</label>
                            <select name="content_type_id" class="form-control" required>
                                <option value="">—</option>
                                @foreach($types as $ct)
                                    <option value="{{ $ct->id }}">{{ $ct->name }} ({{ $ct->records()->count() }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Format</label>
                            <select name="format" class="form-control">
                                <option value="csv">CSV (spreadsheet-compatible)</option>
                                <option value="json">JSON</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="">All records</option>
                                <option value="published">Published only</option>
                                <option value="draft">Draft only</option>
                            </select>
                        </div>
                        <button class="btn btn-primary">Download</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
