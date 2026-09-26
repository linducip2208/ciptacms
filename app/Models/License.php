<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class License extends Model
{
    use HasFactory;

protected $fillable = [
    'license_key',
    'product',
    'customer',
    'domain',
    'status',
    'features',
    'max_activations',
    'expires_at',
    'support_expires_at',
    'version_entitlement',
    'last_checked_at',
];
protected $casts = [
    'features' => 'array',
    'expires_at' => 'datetime',
    'support_expires_at' => 'datetime',
    'last_checked_at' => 'datetime',
];
    public function activations(){ return $this->hasMany(LicenseActivation::class); }

}
