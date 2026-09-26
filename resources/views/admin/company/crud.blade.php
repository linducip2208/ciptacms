@extends('admin.layout')
@section('title', $def['label'])
@section('crumb', 'Company Profile / '.$def['label'])

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form class="d-flex gap-2" method="GET">
        <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search {{ strtolower($def['label']) }}…" style="min-width:220px">
        <select name="status" class="form-control" onchange="this.form.submit()">
            <option value="">All status</option>
            <option value="published" @selected(request('status') === 'published')>Published</option>
            <option value="draft" @selected(request('status') === 'draft')>Draft</option>
        </select>
        <button class="btn btn-primary">Filter</button>
        @if(request('search') || request('status'))
            <a href="{{ route('admin.company.index', $resource) }}" class="btn btn-outline">Reset</a>
        @endif
    </form>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.company.trash', $resource) }}" class="btn btn-outline">Trash</a>
        <a href="{{ route('admin.company.create', $resource) }}" class="btn btn-primary">+ Add {{ $def['singular'] }}</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table" id="cp-table">
            <thead>
                <tr>
                    <th style="width:34px"><input type="checkbox" onclick="document.querySelectorAll('.row-check').forEach(c=>c.checked=this.checked)"></th>
                    @foreach($def['columns'] as $col)
                        <th>{{ ucwords(str_replace('_', ' ', $col)) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr data-id="{{ $row->id }}">
                        <td><input type="checkbox" class="row-check" value="{{ $row->id }}"></td>
                        @foreach($def['columns'] as $col)
                            <td>
                                @if($col === 'actions')
                                    <div class="d-flex gap-2">
                                        <a class="text-indigo-600" href="{{ route('admin.company.edit', [$resource, $row->id]) }}">Edit</a>
                                        <form method="POST" action="{{ route('admin.company.toggle', [$resource, $row->id]) }}">@csrf
                                            <button class="text-slate-500">{{ $row->status === 'published' ? 'Unpublish' : 'Publish' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.company.duplicate', [$resource, $row->id]) }}">@csrf
                                            <button class="text-slate-500">Copy</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.company.destroy', [$resource, $row->id]) }}"
                                              onsubmit="return confirm('Move to trash?')">@csrf @method('DELETE')
                                            <button class="text-rose-600">Trash</button>
                                        </form>
                                    </div>
                                @elseif($col === 'status')
                                    <span class="badge bg-{{ $row->status === 'published' ? 'green' : 'secondary' }}">{{ $row->status ?? '—' }}</span>
                                @elseif($col === 'rating')
                                    <span class="text-warning">{{ str_repeat('★', (int) $row->rating) }}<span class="text-muted">{{ str_repeat('★', 5 - (int) $row->rating) }}</span></span>
                                @elseif(in_array($col, ['image', 'photo', 'logo']))
                                    @if($row->{$col})
                                        <img src="{{ $row->{$col} }}" alt="" style="width:44px;height:34px;object-fit:cover;border-radius:4px">
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                @elseif($col === 'project_date' || $col === 'deadline')
                                    {{ $row->{$col} ? \Illuminate\Support\Carbon::parse($row->{$col})->format('M j, Y') : '—' }}
                                @else
                                    <span class="text-truncate-cell">{{ \Illuminate\Support\Str::limit((string) ($row->{$col} ?? '—'), 70) }}</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($def['columns']) + 1 }}" class="text-center text-muted py-4">
                            No {{ strtolower($def['label']) }} yet.
                            <a href="{{ route('admin.company.create', $resource) }}">Add the first one</a>.
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
@endsection
