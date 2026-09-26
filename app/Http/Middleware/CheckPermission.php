<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request;
class CheckPermission {
    public function handle(Request $r, Closure $next, string $permission) {
        $u = $r->user();
        if (!$u) return redirect()->route('login');
        if (!$u->hasPermission($permission) && !$u->hasRole(['super-admin','admin'])) abort(403, 'Forbidden: '.$permission);
        return $next($r);
    }
}
