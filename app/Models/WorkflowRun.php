<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class WorkflowRun extends Model
{
    use HasFactory;

protected $fillable = [
    'workflow_id',
    'status',
    'payload',
    'log',
    'started_at',
    'finished_at',
];
protected $casts = [
    'payload' => 'array',
];


}
