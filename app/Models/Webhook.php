<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Webhook extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'name',
    'event',
    'url',
    'secret',
    'headers',
    'is_active',
    'timeout',
];
protected $casts = [
    'headers' => 'array',
    'is_active' => 'boolean',
];
    public function logs(){ return $this->hasMany(WebhookLog::class); }

}
