<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Membership extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'member_id',
    'plan',
    'starts_at',
    'ends_at',
    'is_active',
];
protected $casts = [
    'starts_at' => 'datetime',
    'ends_at' => 'datetime',
    'is_active' => 'boolean',
];


}
