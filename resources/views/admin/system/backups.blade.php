@extends('admin.layout')
@section('title', 'Backups')
@section('crumb', 'System / Backup')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h2 class="mb-1">Backups</h2>
        <div class="text-muted">
            Disk <code>{{ setting('storage.backup_disk', config('lindu.backup.disk', 'local')) }}</code> ·
            keeping the most recent {{ (int) setting('storage.keep_backups', config('lindu.backup.keep', 7)) }}
        </div>
    </div>
    <a href="{{ route('admin.backups.restore') }}" class="btn btn-outline">Restore…</a>
</div>

<div class="row g-3 mb-3">
    @foreach(['full' => 'Full (database + files)', 'database' => 'Database only', 'files' => 'Files only'] as $type => $label)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <h3 class="card-title">{{ $label }}</h3>
                    <p class="text-muted small flex-grow-1">
                        {{ $type === 'full' ? 'Database dump plus uploads, careers files and a sanitised .env.example.' : ($type === 'database' ? 'Full SQL dump of every table.' : 'Application files only — no database.') }}
                    </p>
                    <form method="POST" action="{{ route('admin.backups.run') }}">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                        <button class="btn btn-primary w-100">Run {{ $type }} backup</button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Type</th><th>Status</th><th>Archive</th><th>Size</th><th>Finished</th><th>Log</th><th></th></tr></thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td><span class="badge">{{ $r->type }}</span></td>
                        <td>
                            <span class="badge bg-{{ $r->status === 'completed' ? 'green' : ($r->status === 'failed' ? 'red' : 'secondary') }}">
                                {{ $r->status }}
                            </span>
                        </td>
                        <td class="text-muted text-xs" style="max-width:240px">
                            <div class="text-truncate-cell">{{ $r->path ?: '—' }}</div>
                        </td>
                        <td>{{ $r->size ? number_format($r->size / 1024, 1).' KB' : '—' }}</td>
                        <td class="text-muted small">{{ optional($r->finished_at)->diffForHumans() ?? '—' }}</td>
                        <td class="text-muted text-xs" style="max-width:240px">
                            <div class="text-truncate-cell">{{ $r->log ?: '—' }}</div>
                        </td>
                        <td>
                            @if($r->status === 'completed')
                                <form method="POST" action="{{ route('admin.backups.destroy', $r) }}"
                                      onsubmit="return confirm('Delete this backup archive?')">@csrf @method('DELETE')
                                    <button class="text-rose-600">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No backups yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>
@endsection
