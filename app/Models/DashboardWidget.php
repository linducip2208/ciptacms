<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class DashboardWidget extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'user_id',
    'type',
    'title',
    'config',
    'sort_order',
    'width',
    'permission',
    'is_visible',
];
protected $casts = [
    'config' => 'array',
    'is_visible' => 'boolean',
];


}
