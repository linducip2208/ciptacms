@extends('admin.layout')
@section('title', 'System Health')
@section('crumb', 'System / Health')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">System health</h2>
        <div class="text-muted">Live checks against the current installation.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.info') }}" class="btn btn-outline">Environment</a>
        <a href="{{ route('admin.system.cache') }}" class="btn btn-outline">Cache</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card"><div class="card-body text-center">
        <div class="h1 mb-0">PHP {{ $info['php'] }}</div>
        <div class="text-muted text-uppercase" style="font-size:.7rem">Runtime</div>
    </div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body text-center">
        <div class="h1 mb-0">Laravel {{ $info['laravel'] }}</div>
        <div class="text-muted text-uppercase" style="font-size:.7rem">Framework</div>
    </div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body text-center">
        <div class="h1 mb-0">v{{ $info['lindu'] }}</div>
        <div class="text-muted text-uppercase" style="font-size:.7rem">CMS</div>
    </div></div></div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Check</th><th>Status</th><th>Detail</th></tr></thead>
            <tbody>
                @foreach($checks as $key => $check)
                    @php
                        $ok = is_array($check) ? ($check['ok'] ?? false) : (bool) $check;
                        $detail = is_array($check) ? ($check['message'] ?? '') : '';
                    @endphp
                    <tr>
                        <td><b>{{ ucwords(str_replace(['.', '_'], ' ', $key)) }}</b></td>
                        <td>
                            <span class="badge bg-{{ $ok ? 'green' : 'red' }}">{{ $ok ? 'pass' : 'fail' }}</span>
                        </td>
                        <td class="text-muted small">{{ $detail }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
