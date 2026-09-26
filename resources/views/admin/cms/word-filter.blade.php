@extends('admin.layout')
@section('title', 'Word Filter')
@section('crumb', 'Comments / Word Filter')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Word filter</h2>
        <div class="text-muted">New comments containing any of these words are rejected or held for moderation.</div>
    </div>
    <a href="{{ route('admin.cms.comments.index') }}" class="btn btn-outline">← Comments</a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Add a word</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.cms.comments.word-filter.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Word or phrase *</label>
                        <input name="word" value="{{ old('word') }}" class="form-control" required placeholder="casino">
                    </div>
                    <div class="form-group">
                        <label>Action</label>
                        <select name="action" class="form-control">
                            @foreach(['spam' => 'Mark as spam', 'reject' => 'Reject outright', 'hold' => 'Hold for moderation'] as $k => $l)
                                <option value="{{ $k }}">{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-primary">Add word</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Word</th><th>Action</th><th>State</th><th></th></tr></thead>
                    <tbody>
                        @forelse($rows as $w)
                            <tr>
                                <td><code>{{ $w->word }}</code></td>
                                <td><span class="badge">{{ $w->action }}</span></td>
                                <td>
                                    <span class="badge bg-{{ $w->is_active ? 'green' : 'secondary' }}">
                                        {{ $w->is_active ? 'active' : 'off' }}
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.cms.comments.word-filter.destroy', $w) }}"
                                          onsubmit="return confirm('Remove this word?')">@csrf @method('DELETE')
                                        <button class="text-rose-600">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">
                                No words in the filter. Every new comment is passed through unfiltered.
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
