<?php
namespace App\Core\Services;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
class AuditService {
    public function log(string $action, Model|string $entity, array $context = []): void {
        try {
            $class = $entity instanceof Model ? get_class($entity) : (string)$entity;
            $id = $entity instanceof Model ? (string)$entity->getKey() : ($context['entity_id'] ?? null);
            $old = $context['old'] ?? ($entity instanceof Model && $action==='updated' ? $entity->getOriginal() : null);
            $new = $context['new'] ?? ($entity instanceof Model ? $entity->getAttributes() : null);
            AuditLog::create([
                'tenant_id' => app()->bound('tenant') && app('tenant') ? app('tenant')->id : ($context['tenant_id'] ?? null),
                'user_id' => auth()->id() ?? ($context['user_id'] ?? null),
                'action' => $action, 'entity_type' => $class, 'entity_id' => $id,
                'old_values' => $old ? json_encode($old) : null,
                'new_values' => $new ? json_encode($new) : null,
                'ip' => request()->ip(), 'user_agent' => substr((string)request()->userAgent(),0,500),
            ]);
        } catch (\Throwable $e) {}
    }
}
