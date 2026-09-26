<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class CrmActivity extends Model
{
    use HasFactory;

protected $table = 'crm_activities';
protected $fillable = [
    'tenant_id',
    'related_type',
    'related_id',
    'type',
    'subject',
    'body',
    'due_at',
    'completed_at',
    'user_id',
];
protected $casts = [
    'due_at' => 'datetime',
    'completed_at' => 'datetime',
];


}
