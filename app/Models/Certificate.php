<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Certificate extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'course_id',
    'user_id',
    'code',
    'issued_at',
];
protected $casts = [
    'issued_at' => 'datetime',
];


}
