<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Backup extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'type',
    'disk',
    'path',
    'size',
    'status',
    'log',
    'started_at',
    'finished_at',
];
protected $casts = [
    'started_at' => 'datetime',
    'finished_at' => 'datetime',
];


}
