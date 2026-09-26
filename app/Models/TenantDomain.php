<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class TenantDomain extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'domain',
    'is_primary',
    'is_verified',
];
protected $casts = [
    'is_primary' => 'boolean',
    'is_verified' => 'boolean',
];


}
