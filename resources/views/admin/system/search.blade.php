@extends('admin.layout')
@section('title', $q ? 'Search: '.$q : 'Search')
@section('crumb', 'Search')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="mb-0">
        @if($q)
            {{ $total }} result(s) for “{{ $q }}”
        @else
            Global search
        @endif
    </h2>
    <form method="GET" class="d-flex gap-2">
        <input name="q" value="{{ $q }}" class="form-control" placeholder="Search pages, posts, media, users…" autofocus style="min-width:280px">
        <button class="btn btn-primary">Search</button>
    </form>
</div>

@if(!$q)
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            Type at least two characters. Results cover pages, posts, media, users, services and content records.
        </div>
    </div>
@elseif($total === 0)
    <div class="card">
        <div class="card-body text-center text-muted py-5">Nothing matched “{{ $q }}”.</div>
    </div>
@else
    @foreach($groups as $label => $rows)
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">{{ $label }} ({{ $rows->count() }})</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td>
                                    <a href="{{ $row['url'] }}">{{ \Illuminate\Support\Str::limit($row['title'], 80) }}</a>
                                </td>
                                <td class="text-muted small" style="max-width:380px">
                                    <div class="text-truncate-cell">{{ \Illuminate\Support\Str::limit((string) $row['sub'], 90) }}</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
@endif
@endsection
