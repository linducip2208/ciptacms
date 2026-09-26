@extends('admin.layout')
@section('title', 'Trash')
@section('crumb', 'Content / Trash')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Trash</h2>
        <div class="text-muted">Items are kept until you delete them permanently.</div>
    </div>
    <a href="{{ route('admin.cms.pages.index') }}" class="btn btn-outline">← Pages</a>
</div>

@foreach($rows as $type => $items)
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">{{ ucfirst($type) }} ({{ $items->count() }})</h3>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr><th style="width:70px">ID</th><th>Title</th><th>Slug</th><th>Deleted</th><th></th></tr></thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td class="text-muted">{{ $item->id }}</td>
                            <td>{{ $item->title }}</td>
                            <td><code>{{ $item->slug }}</code></td>
                            <td class="text-muted small">{{ $item->deleted_at?->diffForHumans() }}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    <form method="POST" action="{{ route('admin.cms.trash.empty', [$type, $item->id]) }}">
                                        @csrf
                                        <button class="text-indigo-600">Restore</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.cms.trash.force', [$type, $item->id]) }}"
                                          onsubmit="return confirm('Permanently delete this? This cannot be undone.')">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-600">Delete forever</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Nothing in the trash.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endforeach
@endsection
