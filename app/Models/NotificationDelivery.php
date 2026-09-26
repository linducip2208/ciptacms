<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationDelivery extends Model
{
    protected $table = 'notification_deliveries';

    protected $fillable = [
        'tenant_id', 'notification_template_id', 'channel', 'recipient', 'subject',
        'body', 'status', 'error', 'attempts', 'sent_at',
    ];

    protected $casts = ['sent_at' => 'datetime', 'attempts' => 'integer'];

    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    }
}
