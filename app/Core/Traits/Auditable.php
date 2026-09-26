<?php
namespace App\Core\Traits;
use App\Core\Services\AuditService;
trait Auditable {
    public static function bootAuditable(): void {
        foreach (['created','updated','deleted'] as $ev) {
            static::$ev(function ($m) use ($ev) {
                try { app(AuditService::class)->log($ev, $m); } catch (\Throwable $e) {}
            });
        }
    }
}
