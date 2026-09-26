<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Purchase extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'number',
    'supplier_id',
    'subtotal',
    'tax',
    'total',
    'status',
    'purchased_at',
];
protected $casts = [
    'purchased_at' => 'datetime',
];


}
