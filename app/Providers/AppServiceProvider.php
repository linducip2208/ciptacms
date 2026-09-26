<?php

namespace App\Providers;

use App\View\Composers\AdminMenuComposer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // License v3 pairing client (marketplace kit). Stateless — state lives
        // in storage/app/.license.lock + cache — but registered as a singleton
        // so the container reports it as bound for the kit smoke test.
        $this->app->singleton(\App\Services\LicenseClient::class);
    }

    public function boot(): void
    {
        // Admin sidebar is rendered entirely from the menu_items table.
        \Illuminate\Support\Facades\View::composer(['admin.layout', 'admin.*'], AdminMenuComposer::class);

        $this->rateLimiters();

        Gate::before(function ($user, $ability) {
            // Super admin / admin bypass every ability.
            if ($user && $user->hasRole(['super-admin', 'admin'])) {
                return true;
            }

            return null;
        });

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }

    protected function rateLimiters(): void
    {
        RateLimiter::for('api', function (Request $r) {
            $limit = (int) setting('api.rate_limit', config('lindu.api.rate_limit', 60));

            return Limit::perMinute($limit)->by(
                $r->user()?->id ?: $r->ip()
            );
        });

        // Public form posts: tighter than the general API limit because
        // they are reachable by anonymous visitors.
        RateLimiter::for('form-submit', function (Request $r) {
            return Limit::perMinute(20)->by($r->ip());
        });

        RateLimiter::for('webhook-in', function (Request $r) {
            return Limit::perMinute(120)->by($r->ip());
        });

        RateLimiter::for('login', function (Request $r) {
            $max = max(3, (int) setting('security.max_login_attempts', config('lindu.security.max_login_attempts', 5)));

            return Limit::perMinute($max)->by(Str::lower((string) $r->input('email')).'|'.$r->ip());
        });

        // TOTP codes are six digits and short-lived, so the second factor gets
        // its own budget in addition to the one held in the session.
        RateLimiter::for('2fa-challenge', function (Request $r) {
            $max = max(3, (int) setting('security.max_login_attempts', 5));

            return Limit::perMinute($max)->by('2fa|'.$r->session()->get('2fa:user_id', $r->ip()));
        });

        RateLimiter::for('register', function (Request $r) {
            return Limit::perHour(5)->by($r->ip());
        });

        RateLimiter::for('password-reset', function (Request $r) {
            return Limit::perHour(5)->by(Str::lower((string) $r->input('email', '')).'|'.$r->ip());
        });

        // Public comment posting: enough for a conversation, not enough for
        // a spam run.
        RateLimiter::for('comment', function (Request $r) {
            return Limit::perMinute(3)->by($r->ip());
        });
    }
}
