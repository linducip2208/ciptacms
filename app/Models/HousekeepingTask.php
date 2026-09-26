<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class HousekeepingTask extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'property_id',
    'room_id',
    'assigned_to',
    'task',
    'status',
    'due_at',
];
protected $casts = [
    'due_at' => 'datetime',
];


}
