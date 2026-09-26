<?php

namespace App\Http\Middleware;

use App\Services\LicenseClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block ALL routes (admin, user, storefront, anything) until the app is paired
 * to a license. Only `/__pair*` and a small dev-allowlist are accessible
 * without a valid .license.lock for the current host.
 */
class RequirePair
{
    public function __construct(private LicenseClient $client) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldBypass($request)) {
            return $next($request);
        }

        $domain = strtolower($request->getHost());
        $data   = $this->client->verify($domain);

        if ($data) {
            $request->attributes->set('license', $data);
            return $next($request);
        }

        // Not paired (or invalid/tampered) — redirect to wizard
        return redirect()->to('/__pair');
    }

    private function shouldBypass(Request $request): bool
    {
        $path = '/' . ltrim($request->path(), '/');

        // Always allow the wizard itself
        if (str_starts_with($path, '/__pair')) return true;

        // Health check / debug
        if ($path === '/up') return true;
        if (str_starts_with($path, '/_debugbar')) return true;

        // Enforcement can be switched off entirely.
        if (! config('license.enforce', true)) return true;

        // The test suite must never be licence-gated: without this the
        // middleware 302s every request to /__pair and no test can run.
        //
        // Only the loopback hosts bypass automatically. An arbitrary domain
        // such as acme.test still goes through the real check, so the
        // enforcement behaviour stays testable from a test run.
        if (app()->environment('testing') && $this->isLoopbackHost($request->getHost())) {
            return true;
        }

        // Local development: any dev host is fine.
        if (app()->environment('local') && $this->isDevHost($request->getHost())) {
            return true;
        }

        // Optional developer bypass. Never applies in production, and never in
        // the test suite, so the enforcement path stays testable.
        if (config('license.dev_bypass')
            && ! app()->environment('production', 'testing')
            && $this->isDevHost($request->getHost())) {
            return true;
        }

        return false;
    }

    /** Hosts that are unambiguously this machine. */
    private function isLoopbackHost(string $host): bool
    {
        return $host === 'localhost'
            || $host === '127.0.0.1'
            || $host === '::1'
            || str_ends_with($host, '.localhost');
    }

    private function isDevHost(string $host): bool
    {
        return $this->isLoopbackHost($host) || str_ends_with($host, '.test');
    }
}
