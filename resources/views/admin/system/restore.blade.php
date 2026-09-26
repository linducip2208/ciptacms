@extends('admin.layout')
@section('title', 'Restore Backup')
@section('crumb', 'System / Backup / Restore')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Restore a backup</h2>
        <div class="text-muted">Restoring overwrites the current database and application files.</div>
    </div>
    <a href="{{ route('admin.backups.index') }}" class="btn btn-outline">← Backups</a>
</div>

<div class="alert alert-warning">
    A restore replaces live data. Take a fresh backup first, and run this only when you know which
    archive you want. The form below requires an explicit confirmation.
</div>

@if($backups->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">
        No completed backups available. Create one from <a href="{{ route('admin.backups.index') }}">System → Backup</a>.
    </div></div>
@else
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Select an archive</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.backups.restore.run') }}">
                        @csrf
                        <div class="form-group">
                            <label>Backup *</label>
                            <select name="backup_id" class="form-control" required>
                                @foreach($backups as $b)
                                    <option value="{{ $b->id }}">
                                        {{ $b->path }} — {{ number_format($b->size / 1024, 1) }} KB ({{ $b->type }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="confirm" value="1" id="confirm-restore" required>
                            <label class="form-check-label" for="confirm-restore">
                                I understand this overwrites the current database and files.
                            </label>
                        </div>
                        <button class="btn btn-primary text-rose-600">Restore now</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title">What gets restored</h3></div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead><tr><th>Backup</th><th>Type</th><th>Size</th><th>Finished</th><th>Log</th></tr></thead>
                        <tbody>
                            @foreach($backups as $b)
                                <tr>
                                    <td class="text-muted text-xs"><div class="text-truncate-cell">{{ $b->path }}</div></td>
                                    <td><span class="badge">{{ $b->type }}</span></td>
                                    <td>{{ number_format($b->size / 1024, 1) }} KB</td>
                                    <td class="text-muted small">{{ optional($b->finished_at)->diffForHumans() }}</td>
                                    <td class="text-muted text-xs" style="max-width:200px">
                                        <div class="text-truncate-cell">{{ $b->log }}</div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
