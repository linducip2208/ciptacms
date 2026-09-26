<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request;
class CheckInstalled {
    public function handle(Request $r, Closure $next) {
        if (!file_exists(config('lindu.installer_lock')) && !$r->is('install*')) return redirect('/install');
        return $next($r);
    }
}
