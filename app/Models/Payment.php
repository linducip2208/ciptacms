<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Payment extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'order_id',
    'gateway',
    'method',
    'amount',
    'currency',
    'status',
    'reference',
    'meta',
];
protected $casts = [
    'meta' => 'array',
];


}
