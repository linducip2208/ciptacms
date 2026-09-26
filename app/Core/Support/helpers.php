<?php
use App\Core\Services\SettingService;
use App\Models\Tenant;

if (!function_exists('setting')) {
    function setting(string $key, $default = null) {
        try { return app(SettingService::class)->get($key, $default); }
        catch (\Throwable $e) { return config('lindu.'.$key, $default) ?? $default; }
    }
}
if (!function_exists('tenant')) {
    function tenant(): ?Tenant { return app()->bound('tenant') ? app('tenant') : null; }
}
if (!function_exists('tenant_id')) {
    function tenant_id(): ?string { return tenant()?->id; }
}
if (!function_exists('lindu_version')) {
    function lindu_version(): string { return config('lindu.version','1.0.0'); }
}
if (!function_exists('user_can')) {
    function user_can(string $permission): bool {
        $u = auth()->user(); if (!$u) return false;
        if (method_exists($u,'hasPermission')) return $u->hasPermission($permission);
        return false;
    }
}
