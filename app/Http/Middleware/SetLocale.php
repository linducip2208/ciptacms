<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request;
class SetLocale {
    public function handle(Request $r, Closure $next) {
        $locale = $r->user()->locale ?? session('locale', config('app.locale'));
        try { $locale = app(\App\Core\Services\SettingService::class)->get('general.locale', $locale); } catch(\Throwable $e){}
        app()->setLocale($locale);
        return $next($r);
    }
}
