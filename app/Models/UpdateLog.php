<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class UpdateLog extends Model
{
    use HasFactory;

protected $fillable = [
    'type',
    'slug',
    'from_version',
    'to_version',
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
