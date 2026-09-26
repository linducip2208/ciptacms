@extends('admin.layout')
@section('title', 'Import')
@section('crumb', 'Data / Import')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Import records</h2>
        <div class="text-muted">CSV columns must match the field slugs of the chosen content type. Unknown columns are ignored.</div>
    </div>
    <a href="{{ route('admin.cms.export') }}" class="btn btn-outline">Export instead</a>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Upload</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.cms.import.run') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label>Content type *</label>
                        <select name="content_type_id" class="form-control" required>
                            <option value="">—</option>
                            @foreach($types as $ct)
                                <option value="{{ $ct->id }}" @selected((int) old('content_type_id') === $ct->id)>{{ $ct->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Format *</label>
                        <select name="format" class="form-control">
                            <option value="csv">CSV</option>
                            <option value="json">JSON</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mode *</label>
                        <select name="mode" class="form-control">
                            <option value="insert">Insert new records only</option>
                            <option value="update">Update by key field</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Key field (for update mode)</label>
                        <input name="key_field" value="{{ old('key_field') }}" class="form-control" placeholder="sku">
                    </div>
                    <div class="form-group">
                        <label>File * (max 20MB)</label>
                        <input type="file" name="file" class="form-control" required accept=".csv,.json,text/csv,application/json">
                    </div>
                    <button class="btn btn-primary">Import</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Row errors</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Line</th><th>Reason</th><th>Data</th></tr></thead>
                    <tbody>
                        @forelse(session('import_errors', []) as $e)
                            <tr>
                                <td>{{ $e['line'] }}</td>
                                <td class="text-rose-600">{{ $e['reason'] }}</td>
                                <td class="text-muted text-xs" style="max-width:260px">
                                    <div class="text-truncate-cell">{{ json_encode($e['row']) }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">
                                No errors from the last import.
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">CSV format</h3></div>
            <div class="card-body">
<pre class="font-monospace text-xs mb-0" style="white-space:pre-wrap">name,price,featured
"Widget A",149000,true
"Widget B",99000,false</pre>
                <small class="text-muted d-block mt-2">
                    The first row must be the header. Use field slugs exactly as they appear in the content type.
                </small>
            </div>
        </div>
    </div>
</div>
@endsection
