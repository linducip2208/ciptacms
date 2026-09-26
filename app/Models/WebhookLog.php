<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class WebhookLog extends Model
{
    use HasFactory;

protected $fillable = [
    'webhook_id',
    'event',
    'payload',
    'status',
    'attempts',
    'response',
    'next_retry_at',
];
protected $casts = [
    'payload' => 'array',
];


}
