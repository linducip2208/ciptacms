<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install {{ config('lindu.name', 'Lindu CMS') }} — setup</title>
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
        input[type=text],input[type=email],input[type=password],input[type=url],input[type=number],select{
            width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px;font:inherit;font-size:.95rem}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        @media(max-width:640px){.grid{grid-template-columns:1fr}}
        .btn{display:inline-block;background:#1d4ed8;color:#fff;padding:12px 24px;border:0;border-radius:8px;font:inherit;font-weight:600;cursor:pointer;text-decoration:none}
        .btn-outline{background:transparent;color:#1d4ed8;border:1px solid #1d4ed8}
        .alert{padding:12px 16px;border-radius:8px;margin-bottom:18px}
        .alert-ok{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
        .alert-err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
        .alert-warn{background:#fffbeb;color:#92400e;border:1px solid #fde68a}
        .hint{font-size:.85rem;color:#64748b;margin-top:4px}
        fieldset{border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin:0 0 16px}
        legend{font-weight:600;font-size:.9rem;padding:0 6px}
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Application setup</h1>
        <p class="muted">Version {{ config('lindu.version') }}</p>

        <ol class="steps">
            <li class="done">1 · Requirements</li>
            <li class="current">2 · Database</li>
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

        <form method="POST" action="{{ route('install.run') }}">
            @csrf

            <fieldset>
                <legend>Database</legend>
                <div class="row">
                    <label for="db_connection">Connection</label>
                    <select id="db_connection" name="db_connection" onchange="toggleDb()">
                        @foreach(['mysql' => 'MySQL / MariaDB', 'pgsql' => 'PostgreSQL', 'sqlite' => 'SQLite (no server)'] as $k => $l)
                            <option value="{{ $k }}" @selected($default === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="server-fields">
                    <div class="grid">
                        <div class="row">
                            <label for="db_host">Host</label>
                            <input id="db_host" type="text" name="db_host" value="127.0.0.1" autocomplete="off">
                        </div>
                        <div class="row">
                            <label for="db_port">Port</label>
                            <input id="db_port" type="number" name="db_port" value="3306" autocomplete="off">
                        </div>
                        <div class="row">
                            <label for="db_name">Database name *</label>
                            <input id="db_name" type="text" name="db_name" autocomplete="off">
                        </div>
                        <div class="row">
                            <label for="db_user">Username *</label>
                            <input id="db_user" type="text" name="db_user" autocomplete="off">
                        </div>
                    </div>
                    <div class="row">
                        <label for="db_pass">Password</label>
                        <input id="db_pass" type="password" name="db_pass" autocomplete="new-password">
                    </div>
                </div>

                <div id="sqlite-fields" style="display:none">
                    <div class="row">
                        <label for="db_database">Database file</label>
                        <input id="db_database" type="text" name="db_database" value="{{ database_path('database.sqlite') }}">
                        <div class="hint">Created automatically if it does not exist.</div>
                    </div>
                </div>

                <p class="hint">
                    Credentials are written to <code>.env</code>. They are not validated before
                    migrations run, so a wrong host fails at the migration step.
                </p>
            </fieldset>

            <fieldset>
                <legend>Application</legend>
                <div class="grid">
                    <div class="row">
                        <label for="app_name">Site name *</label>
                        <input id="app_name" type="text" name="app_name" value="Lindu CMS" required>
                    </div>
                    <div class="row">
                        <label for="app_url">Site URL</label>
                        <input id="app_url" type="url" name="app_url" value="{{ config('app.url') }}">
                    </div>
                </div>
                <div class="row">
                    <label for="tagline">Tagline</label>
                    <input id="tagline" type="text" name="tagline" placeholder="Building digital products that grow with you.">
                </div>
            </fieldset>

            <fieldset>
                <legend>Administrator account</legend>
                <div class="grid">
                    <div class="row">
                        <label for="admin_name">Name *</label>
                        <input id="admin_name" type="text" name="admin_name" required autocomplete="name">
                    </div>
                    <div class="row">
                        <label for="admin_email">Email *</label>
                        <input id="admin_email" type="email" name="admin_email" required autocomplete="email">
                    </div>
                    <div class="row">
                        <label for="admin_password">Password * (min 8)</label>
                        <input id="admin_password" type="password" name="admin_password" required autocomplete="new-password">
                    </div>
                    <div class="row">
                        <label for="admin_password_confirmation">Confirm password *</label>
                        <input id="admin_password_confirmation" type="password" name="admin_password_confirmation" required autocomplete="new-password">
                    </div>
                </div>
            </fieldset>

            <div class="alert alert-warn">
                This runs every migration, seeds the database, creates the admin account and
                writes an install lock. Make sure you have a database backup if one already exists.
            </div>

            <div style="display:flex;gap:12px;flex-wrap:wrap">
                <button class="btn">Run installer</button>
                <a href="/install" class="btn btn-outline">← Back to requirements</a>
            </div>
        </form>
    </div>

    <p class="muted" style="font-size:.85rem;text-align:center">
        After install, <code>/install</code> locks. Reopen with
        <code>php artisan lindu:unlock --force</code>.
    </p>
</div>

<script>
function toggleDb() {
    const isSqlite = document.getElementById('db_connection').value === 'sqlite';
    document.getElementById('server-fields').style.display = isSqlite ? 'none' : '';
    document.getElementById('sqlite-fields').style.display = isSqlite ? '' : 'none';
}
document.addEventListener('DOMContentLoaded', toggleDb);
</script>
</body>
</html>
