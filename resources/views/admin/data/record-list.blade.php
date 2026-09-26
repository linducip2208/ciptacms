@extends('admin.layout')
@section('title', $ct->name.' Records')
@section('crumb', 'Data / Records / '.$ct->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form class="d-flex gap-2" method="GET">
        <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search…" style="min-width:200px">
        <select name="status" class="form-control" onchange="this.form.submit()">
            <option value="">All status</option>
            <option value="published" @selected(request('status') === 'published')>Published</option>
            <option value="draft" @selected(request('status') === 'draft')>Draft</option>
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.cms.export', ['content_type_id' => $ct->id]) }}" class="btn btn-outline">Export</a>
        <a href="{{ route('admin.cms.records.create', $ct) }}" class="btn btn-primary">+ New record</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.cms.records.bulk', $ct) }}">
    @csrf
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">{{ $ct->name }} ({{ $rows->total() }})</h3>
            <div class="d-flex gap-2">
                <select name="action" class="form-control form-control-sm" style="width:auto">
                    <option value="publish">Publish</option>
                    <option value="draft">Move to draft</option>
                    <option value="delete">Delete</option>
                </select>
                <button class="btn btn-sm btn-outline" onclick="return confirm('Apply to selected records?')">Apply to selected</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th style="width:34px"><input type="checkbox" onclick="document.querySelectorAll('.rec-check').forEach(c=>c.checked=this.checked)"></th>
                        <th style="width:60px">ID</th>
                        @foreach($columns as $c)
                            <th>{{ $c['name'] ?? $c['slug'] }}</th>
                        @endforeach
                        <th>Status</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @php $names = array_map(fn ($n) => 'ids[]', (array) $rows->pluck('id')); @endphp
                    @forelse($rows as $r)
                        <tr>
                            <td><input type="checkbox" class="rec-check" name="ids[]" value="{{ $r->id }}"></td>
                            <td class="text-muted">{{ $r->id }}</td>
                            @foreach($columns as $c)
                                @php $v = (array) $r->data; $val = $v[$c['slug']] ?? null; @endphp
                                <td style="max-width:180px">
                                    <div class="text-truncate-cell">
                                        @if(is_array($val))
                                            {{ \Illuminate\Support\Str::limit(implode(', ', array_map(fn($x) => is_scalar($x) ? $x : json_encode($x), $val)), 50) }}
                                        @elseif(is_bool($val))
                                            {{ $val ? 'Yes' : 'No' }}
                                        @else
                                            {{ \Illuminate\Support\Str::limit((string) ($val ?? '—'), 50) }}
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                            <td><span class="badge bg-{{ $r->status === 'published' ? 'green' : 'secondary' }}">{{ $r->status }}</span></td>
                            <td class="text-muted small">{{ optional($r->created_at)->diffForHumans() }}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a class="text-indigo-600" href="{{ route('admin.cms.records.edit', [$ct, $r->id]) }}">Edit</a>
                                    <form method="POST" action="{{ route('admin.cms.records.destroy', [$ct, $r->id]) }}"
                                          onsubmit="return confirm('Delete this record?')">@csrf @method('DELETE')
                                        <button class="text-rose-600">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($columns) + 5 }}" class="text-center text-muted py-4">
                            No records yet. <a href="{{ route('admin.cms.records.create', $ct) }}">Create the first one</a>.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
    </div>
</form>
@endsection
