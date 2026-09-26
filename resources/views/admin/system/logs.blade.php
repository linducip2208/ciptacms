@extends('admin.layout')
@section('title', 'Logs')
@section('crumb', 'System / Logs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form class="d-flex gap-2" method="GET">
        <select name="level" class="form-control" onchange="this.form.submit()">
            <option value="">All levels</option>
            @foreach($levels as $l)
                <option value="{{ $l }}" @selected($level === $l)>{{ $l }}</option>
            @endforeach
        </select>
    </form>
    <div class="d-flex gap-2">
        @if($exists)
            <a href="{{ route('admin.system.logs.download') }}" class="btn btn-outline">Download</a>
            <form method="POST" action="{{ route('admin.system.logs.clear') }}"
                  onsubmit="return confirm('Truncate every log file? This cannot be undone.')">
                @csrf
                <button class="btn btn-outline text-rose-600">Clear logs</button>
            </form>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">
            storage/logs/laravel.log
            @if($exists)
                <span class="text-muted text-xs">({{ number_format($size / 1024, 1) }} KB — showing the most recent {{ count($lines) }} entries)</span>
            @endif
        </h3>
    </div>
    <div class="card-body">
        @if(!$exists)
            <p class="text-muted mb-0">No log file present yet. One is created on the first loggable event.</p>
        @else
            <div style="max-height:600px;overflow:auto">
                <table class="table table-vcenter card-table">
                    <thead><tr><th style="width:90px">Level</th><th style="width:170px">Time</th><th>Message</th></tr></thead>
                    <tbody>
                        @foreach($lines as $line)
                            <tr>
                                <td>
                                    <span class="badge bg-{{ match($line['level'] ?? '') {
                                        'error', 'critical', 'alert', 'emergency' => 'red',
                                        'warning' => 'orange', default => 'secondary',
                                    } }}">{{ $line['level'] ?? '?' }}</span>
                                </td>
                                <td class="text-muted text-xs">{{ $line['datetime'] ?? '' }}</td>
                                <td style="max-width:600px">
                                    <div class="text-truncate-cell">{{ $line['message'] ?? '' }}</div>
                                    @if(!empty($line['exception']))
                                        <div class="text-muted text-xs">{{ \Illuminate\Support\Str::limit((string) $line['exception'], 140) }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
