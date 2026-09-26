<?php

namespace App\Http\Controllers;

use App\Core\Services\HealthService;
use App\Core\Services\InstallLock;
use App\Core\Services\SettingService;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Browser installer.
 *
 * Refuses to run once InstallLock says the application is installed. There is
 * no way back in through the web alone — recovery is either the
 * `lindu:unlock` console command or a token derived from APP_KEY.
 */
class InstallerController extends Controller
{
    public function __construct(protected InstallLock $lock) {}

    protected function guard()
    {
        if (! $this->lock->installed()) {
            return null;
        }

        if ($this->lock->verifyToken(request('recovery_token'))) {
            // Valid token: allow this one request, then re-lock.
            return null;
        }

        return redirect('/login')->with(
            'ok',
            'Lindu CMS is already installed'
                .($this->lock->installedAt() ? ' on '.$this->lock->installedAt() : '')
                .'. The installer is locked.'
        );
    }

    public function index()
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }

        return view('installer.welcome', [
            'checks' => app(HealthService::class)->checks(),
        ]);
    }

    public function database(Request $r)
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }

        // The install must not proceed on a host that cannot run the product.
        $failed = array_filter(
            app(HealthService::class)->checks(),
            fn ($c) => is_array($c) && array_key_exists('ok', $c) && ! $c['ok']
        );

        if ($failed) {
            return redirect('/install')->withErrors([
                'requirements' => 'Fix the failing requirement(s) before continuing: '
                    .implode(', ', array_keys($failed)),
            ]);
        }

        return view('installer.database', ['default' => config('database.default')]);
    }

    public function run(Request $r)
    {
        if ($redirect = $this->guard()) {
            return $redirect;
        }

        $d = $r->validate([
            'app_name' => 'required|string|max:190',
            'app_url' => 'nullable|url|max:255',
            'db_connection' => 'nullable|in:mysql,sqlite,pgsql,mariadb',
            'db_host' => 'nullable|string|max:190',
            'db_port' => 'nullable|string|max:10',
            'db_name' => 'nullable|string|max:190',
            'db_user' => 'nullable|string|max:190',
            'db_pass' => 'nullable|string|max:190',
            'db_database' => 'nullable|string|max:190',
            'admin_name' => 'required|string|max:190',
            'admin_email' => 'required|email|max:190',
            'admin_password' => 'required|min:8|confirmed',
            'tagline' => 'nullable|string|max:190',
        ]);

        try {
            $this->configureDatabase($d);

            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);

            $user = User::where('email', $d['admin_email'])->first();
            if (! $user) {
                $user = User::create([
                    'name' => $d['admin_name'],
                    'email' => $d['admin_email'],
                    'password' => Hash::make($d['admin_password']),
                    'status' => 'active',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]);
            } else {
                $user->update([
                    'name' => $d['admin_name'],
                    'password' => Hash::make($d['admin_password']),
                ]);
            }

            $role = Role::where('slug', 'super-admin')->first() ?: Role::where('slug', 'admin')->first();
            if ($role && ! $user->roles()->where('role_id', $role->id)->exists()) {
                $user->roles()->attach($role);
            }

            $settings = app(SettingService::class);
            $settings->set('general.site_name', $d['app_name'], 'text', 'general');
            $settings->set('general.tagline', $d['tagline'] ?: 'Building digital products that grow with you.', 'text', 'general');

            $this->lock->lock(
                $d['app_url'] ?: config('app.url'),
                $d['app_name'],
                $d['admin_email']
            );

            return redirect('/login')->with('ok', 'Installed. Sign in with '.$d['admin_email'].'.');
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Write the chosen database credentials into .env.
     *
     * Only the DB_* and APP_URL keys are touched, and APP_KEY is never
     * generated here — losing it would invalidate every encrypted setting,
     * session and license lock already written.
     */
    protected function configureDatabase(array $d): void
    {
        $env = base_path('.env');
        if (! File::exists($env)) {
            File::copy(base_path('.env.example'), $env);
        }

        $lines = File::get($env);
        $set = [
            'APP_URL' => $d['app_url'] ?: config('app.url'),
            'APP_NAME' => '"'.str_replace('"', '', $d['app_name']).'"',
        ];

        $connection = $d['db_connection'] ?: config('database.default');

        // SQLite needs no server credentials.
        if ($connection === 'sqlite') {
            $dbPath = $d['db_database'] ?: database_path('database.sqlite');
            if (! File::exists($dbPath)) {
                File::put($dbPath, '');
            }
            $set['DB_CONNECTION'] = 'sqlite';
            $set['DB_DATABASE'] = $dbPath;
        } else {
            $set['DB_CONNECTION'] = $connection;
            $set['DB_HOST'] = $d['db_host'] ?? '127.0.0.1';
            $set['DB_PORT'] = (string) ($d['db_port'] ?? 3306);
            $set['DB_DATABASE'] = $d['db_name'] ?? '';
            $set['DB_USERNAME'] = $d['db_user'] ?? '';
            $set['DB_PASSWORD'] = $d['db_pass'] ?? '';
        }

        foreach ($set as $key => $value) {
            if (preg_match('/^'.preg_quote($key, '/').'=/m', $lines)) {
                $lines = preg_replace('/^'.preg_quote($key, '/').'=.*$/m', $key.'='.$value, $lines);
            } else {
                $lines .= "\n{$key}={$value}\n";
            }
        }

        File::put($env, $lines);
    }
}
