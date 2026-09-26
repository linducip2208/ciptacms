<?php

namespace App\Http\Controllers\Admin;

use App\Core\Services\AuditService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    protected function ok($data = [], $msg = 'OK')
    {
        return response()->json(['ok' => true, 'message' => $msg, 'data' => $data]);
    }

    protected function audit(string $action, $model, Request $r)
    {
        try {
            app(AuditService::class)->log($action, $model, [
                'user_id' => $r->user()?->id,
                'ip' => $r->ip(),
            ]);
        } catch (\Throwable $e) {
            // Auditing must never break the request it is recording.
        }
    }

    /**
     * Run a query, returning a fallback if the schema is not ready or the
     * driver errors. The admin is reachable between the installer writing
     * the database config and migrations completing, so panels should degrade
     * rather than 500.
     */
    protected function safe(callable $fn, $fallback = null)
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            report($e);

            return $fallback;
        }
    }
}
