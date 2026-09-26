<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Workflow extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'name',
    'trigger_event',
    'conditions',
    'actions',
    'is_active',
];
protected $casts = [
    'conditions' => 'array',
    'actions' => 'array',
    'is_active' => 'boolean',
];
    public function runs(){ return $this->hasMany(WorkflowRun::class); }

}
