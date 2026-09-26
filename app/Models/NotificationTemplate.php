<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class NotificationTemplate extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'slug',
    'name',
    'channel',
    'subject',
    'body',
    'variables',
];
protected $casts = [
    'variables' => 'array',
];


}
