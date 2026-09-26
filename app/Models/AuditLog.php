<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class AuditLog extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'user_id',
    'action',
    'entity_type',
    'entity_id',
    'old_values',
    'new_values',
    'ip',
    'user_agent',
];


}
