<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request;
class ResolveTenant {
    public function handle(Request $r, Closure $next) {
        try { app(\App\Core\Services\TenantService::class)->resolve($r->getHost()); } catch(\Throwable $e){}
        return $next($r);
    }
}
