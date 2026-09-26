@extends('admin.layout')
@section('title', 'Scheduled Tasks')
@section('crumb', 'System / Scheduled Tasks')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Scheduled tasks</h2>
        <div class="text-muted">
            Lindu schedules jobs in <code>app/Console/Kernel.php</code>. The cron entry below must run once a minute
            for any of this to fire.
        </div>
    </div>
    <a href="{{ route('admin.health') }}" class="btn btn-outline">Health</a>
</div>

<div class="card mb-3">
    <div class="card-header"><h3 class="card-title">Cron entry</h3></div>
    <div class="card-body">
<pre class="font-monospace text-xs mb-0" style="white-space:pre-wrap">* * * * * cd {{ base_path() }} &amp;&amp; php artisan schedule:run >> /dev/null 2&gt;&amp;1</pre>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Available artisan commands ({{ count($commands) }})</h3></div>
    <div class="table-responsive" style="max-height:560px;overflow:auto">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Command</th><th>Description</th></tr></thead>
            <tbody>
                @foreach($commands as $c)
                    <tr>
                        <td><code>{{ $c['name'] }}</code></td>
                        <td class="text-muted small">{{ $c['description'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted small">
        Verify what actually runs with <code>php artisan schedule:list</code> on the server.
    </div>
</div>
@endsection
