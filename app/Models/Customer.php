<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Customer extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'name',
    'email',
    'phone',
    'address',
    'city',
    'meta',
    'total_orders',
    'total_spent',
];
protected $casts = [
    'meta' => 'array',
];


}
