<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class LoginHistory extends Model
{
    use HasFactory;

protected $fillable = [
    'user_id',
    'ip',
    'user_agent',
    'status',
    'failed_reason',
    'logged_in_at',
];
protected $casts = [
    'logged_in_at' => 'datetime',
];


}
