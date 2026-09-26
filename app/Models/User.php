<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id', 'name', 'email', 'password', 'username', 'phone', 'avatar',
        'status', 'is_active', 'email_verified_at', 'last_login_at', 'last_login_ip',
        'two_factor_secret', 'two_factor_enabled', 'two_factor_backup_codes',
        'preferences', 'locale', 'timezone',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'two_factor_backup_codes' => 'array',
        'preferences' => 'array',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'tenant_user')->withPivot('role_id');
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->roles()->whereHas('permissions', fn ($q) => $q->where('slug', $slug))->exists()) {
            return true;
        }

        if ($this->roles()->whereIn('slug', ['super-admin', 'admin'])->exists()) {
            return true;
        }

        return false;
    }

    public function hasRole(string|array $roles): bool
    {
        return $this->roles()->whereIn('slug', (array) $roles)->exists();
    }

    public function isActive(): bool
    {
        return (bool) $this->is_active && $this->status === 'active';
    }

    public function loginHistories()
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function socialAccounts()
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function memberProfile()
    {
        return $this->hasOne(MemberProfile::class);
    }

    public function vendor()
    {
        return $this->hasOne(Vendor::class);
    }

    /**
     * Laravel's authorization entry point, so $user->can('media.delete')
     * works. A dotted slug maps to a permission; a model instance or class
     * falls through to the policy layer.
     */
    public function can($abilities, $arguments = []): bool
    {
        if (is_string($abilities) && ! str_contains($abilities, '\\') && ! str_contains($abilities, '|')) {
            return $this->hasPermission($abilities);
        }

        return parent::can($abilities, $arguments);
    }
}
