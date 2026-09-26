@extends('admin.layout')
@section('title', 'Trash — '.$def['label'])
@section('crumb', 'Company Profile / '.$def['label'].' / Trash')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Trash — {{ $def['label'] }}</h2>
    <a href="{{ route('admin.company.index', $resource) }}" class="btn btn-outline">← Back</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>ID</th><th>Item</th><th>Deleted</th><th></th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td>{{ \Illuminate\Support\Str::limit((string) ($row->title ?? $row->name ?? $row->position ?? $row->question ?? $row->customer ?? '—'), 90) }}</td>
                        <td class="text-muted">{{ optional($row->deleted_at)->diffForHumans() }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.company.restore', [$resource, $row->id]) }}">@csrf
                                <button class="text-indigo-600">Restore</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">Trash is empty.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>
@endsection
