<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install {{ config('lindu.name', 'Lindu CMS') }}</title>
    <style>
        *{box-sizing:border-box}
        body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#f1f5f9;color:#0f172a;line-height:1.6}
        .wrap{max-width:760px;margin:0 auto;padding:40px 20px}
        .card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:28px;margin-bottom:20px}
        h1{font-size:1.7rem;margin:0 0 4px}
        .muted{color:#64748b}
        .steps{display:flex;gap:8px;margin:0 0 24px;padding:0;list-style:none;flex-wrap:wrap}
        .steps li{font-size:.8rem;padding:4px 12px;border-radius:99px;background:#e2e8f0;color:#64748b}
        .steps li.done{background:#dcfce7;color:#166534}
        .steps li.current{background:#1d4ed8;color:#fff}
        .row{margin-bottom:16px}
        label{display:block;font-weight:600;margin-bottom:6px;font-size:.9rem}
        input[type=text],input[type=email],input[type=password],input[type=url],input[type=number],select,textarea{
            width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font:inherit;font-size:.95rem}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        @media(max-width:640px){.grid{grid-template-columns:1fr}}
        .check{display:flex;justify-content:space-between;gap:12px;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:6px;font-size:.9rem}
        .pass{color:#166534;font-weight:600}
        .fail{color:#991b1b;font-weight:600}
        .btn{display:inline-block;background:#1d4ed8;color:#fff;padding:12px 24px;border:0;border-radius:8px;font:inherit;font-weight:600;cursor:pointer;text-decoration:none}
        .btn-outline{background:transparent;color:#1d4ed8;border:1px solid #1d4ed8}
        .alert{padding:12px 16px;border-radius:8px;margin-bottom:18px}
        .alert-ok{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
        .alert-err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
        .hint{font-size:.85rem;color:#64748b;margin-top:4px}
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Install {{ config('lindu.name', 'Lindu CMS') }}</h1>
        <p class="muted">Version {{ config('lindu.version') }}</p>

        <ol class="steps">
            <li class="current">1 · Requirements</li>
            <li>2 · Database</li>
            <li>3 · Application</li>
            <li>4 · Admin account</li>
            <li>5 · Finish</li>
        </ol>

        @if(session('ok'))
            <div class="alert alert-ok">{{ session('ok') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-err">
                <ul style="margin:0;padding-left:18px">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <h2 style="font-size:1.1rem">Server requirements</h2>
        <p class="muted" style="margin-top:0">
            Every check must pass before the install can continue.
        </p>

        <div>
            @foreach($checks as $name => $check)
                @php
                    $ok = is_array($check) ? ($check['ok'] ?? false) : (bool) $check;
                    $label = is_array($check) ? ($check['label'] ?? $name) : $name;
                    $value = is_array($check) ? ($check['value'] ?? '') : '';
                @endphp
                <div class="check">
                    <span>
                        {{ $label }}
                        @if($value)<span class="muted" style="font-size:.8rem"> — {{ \Illuminate\Support\Str::limit((string) $value, 60) }}</span>@endif
                    </span>
                    <span class="{{ $ok ? 'pass' : 'fail' }}">{{ $ok ? 'PASS' : 'FAIL' }}</span>
                </div>
            @endforeach
        </div>

        @php $blocking = array_filter($checks, fn ($c) => is_array($c) && array_key_exists('ok', $c) && ! $c['ok']); @endphp

        <div style="margin-top:24px;display:flex;gap:12px;align-items:center;flex-wrap:wrap">
            @if($blocking)
                <button class="btn" disabled style="opacity:.5;cursor:not-allowed">Fix the failing checks to continue</button>
                <a href="/install" class="btn btn-outline">Re-check</a>
            @else
                <a href="/install/database" class="btn">Continue to database setup →</a>
            @endif
        </div>
    </div>

    <p class="muted" style="font-size:.85rem;text-align:center">
        Once installation finishes this page locks itself. Reopening it needs
        <code>php artisan lindu:unlock --force</code> on the server.
    </p>
</div>
</body>
</html>
