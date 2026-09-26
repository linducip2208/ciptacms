<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'tenant_id', 'user_id', 'channel', 'action', 'description',
        'subject_type', 'subject_id', 'properties', 'ip',
    ];

    protected $casts = ['properties' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
