<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanFeature extends Model
{
    protected $table = 'plan_features';

    protected $fillable = ['plan_id', 'key', 'is_enabled', 'limits'];

    protected $casts = [
        'is_enabled' => 'boolean',
        'limits' => 'array',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
