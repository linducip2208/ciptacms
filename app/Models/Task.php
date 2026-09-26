<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Task extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'title',
    'description',
    'status',
    'priority',
    'assigned_to',
    'created_by',
    'due_at',
    'completed_at',
];
protected $casts = [
    'due_at' => 'datetime',
    'completed_at' => 'datetime',
];
    public function comments(){ return $this->hasMany(TaskComment::class); }

}
