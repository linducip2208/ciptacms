@extends('admin.layout')
@section('title', 'Database')
@section('crumb', 'System / Database')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Database</h2>
        <div class="text-muted">
            Connection <code>{{ $driver }}</code> · {{ count($tables) }} tables · {{ number_format($totalRows) }} rows in the key tables
        </div>
    </div>
    <a href="{{ route('admin.system.schedule') }}" class="btn btn-outline">Migrations</a>
</div>

<div class="card">
    <div class="table-responsive" style="max-height:640px;overflow:auto">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Table</th><th>Rows</th><th>Size</th></tr></thead>
            <tbody>
                @forelse($tables as $t)
                    <tr>
                        <td><code>{{ $t['name'] }}</code></td>
                        <td>{{ $t['rows'] === null ? '—' : number_format($t['rows']) }}</td>
                        <td class="text-muted text-xs">
                            {{ $t['size'] > 0 ? number_format($t['size'] / 1024, 1).' KB' : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">Could not read the schema.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted small">
        Row counts run one query per table. On a large install prefer
        <code>php artisan db:table</code> or your database console.
    </div>
</div>

<div class="card mt-3">
    <div class="card-header"><h3 class="card-title">Run an artisan command</h3></div>
    <div class="card-body">
        <p class="text-muted small">
            Destructive commands (<code>migrate:fresh</code>, <code>db:wipe</code>, <code>env:clear</code> and friends)
            are blocked here on purpose — run those from a terminal.
        </p>
        <form method="POST" action="{{ route('admin.system.artisan') }}" class="d-flex gap-2">
            @csrf
            <input name="command" class="form-control font-monospace" placeholder="migrate:status" required>
            <button class="btn btn-primary">Run</button>
        </form>
    </div>
</div>
@endsection
