<?php
namespace App\Policies;
use App\Models\User;
class GenericPolicy {
    public function __construct(protected string $resource='') {}
    public function viewAny(User $u): bool { return $u->hasPermission($this->resource.'.view') || $u->hasRole(['super-admin','admin']); }
    public function view(User $u): bool { return $u->hasPermission($this->resource.'.read') || $u->hasRole(['super-admin','admin']); }
    public function create(User $u): bool { return $u->hasPermission($this->resource.'.create') || $u->hasRole(['super-admin','admin']); }
    public function update(User $u): bool { return $u->hasPermission($this->resource.'.update') || $u->hasRole(['super-admin','admin']); }
    public function delete(User $u): bool { return $u->hasPermission($this->resource.'.delete') || $u->hasRole(['super-admin','admin']); }
}
