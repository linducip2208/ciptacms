<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Enrollment extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'course_id',
    'user_id',
    'status',
    'progress',
    'enrolled_at',
    'completed_at',
];
protected $casts = [
    'enrolled_at' => 'datetime',
    'completed_at' => 'datetime',
];


}
