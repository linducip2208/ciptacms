@extends('admin.layout')
@section('title', 'Content Types')
@section('crumb', 'Data / Content Types')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Content types</h2>
        <div class="text-muted">Dynamic record types with their own fields, CRUD, API and import/export.</div>
    </div>
    <a href="{{ route('admin.cms.types.create') }}" class="btn btn-primary">+ New content type</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Name</th><th>Slug</th><th>Table</th><th>Fields</th><th>Records</th><th>API</th><th></th></tr></thead>
            <tbody>
                @forelse($rows as $ct)
                    <tr>
                        <td>
                            <b>{{ $ct->icon }} {{ $ct->name }}</b>
                            @if($ct->description)<div class="text-muted small">{{ \Illuminate\Support\Str::limit($ct->description, 60) }}</div>@endif
                        </td>
                        <td><code>{{ $ct->slug }}</code></td>
                        <td><code class="text-xs">{{ $ct->table_name ?: '—' }}</code></td>
                        <td>{{ count($ct->fields ?? []) }}</td>
                        <td>{{ $ct->records_count }}</td>
                        <td>
                            <span class="badge bg-{{ $ct->is_api_enabled ? 'green' : 'secondary' }}">
                                {{ $ct->is_api_enabled ? 'enabled' : 'disabled' }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a class="text-indigo-600" href="{{ route('admin.cms.types.edit', $ct) }}">Edit</a>
                                <a class="text-slate-600" href="{{ route('admin.cms.records.list', $ct) }}">Records</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">
                        No content types yet. <a href="{{ route('admin.cms.types.create') }}">Create the first one</a>.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
