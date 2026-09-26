<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Subscription extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'plan_id',
    'status',
    'trial_ends_at',
    'current_period_start',
    'current_period_end',
    'cancelled_at',
];
protected $casts = [
    'trial_ends_at' => 'datetime',
    'current_period_start' => 'datetime',
    'current_period_end' => 'datetime',
    'cancelled_at' => 'datetime',
];


}
