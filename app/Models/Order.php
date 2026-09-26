<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Order extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'vendor_id',
    'customer_id',
    'number',
    'status',
    'payment_status',
    'subtotal',
    'discount',
    'tax',
    'shipping',
    'total',
    'currency',
    'notes',
    'meta',
    'ordered_at',
];
protected $casts = [
    'meta' => 'array',
    'ordered_at' => 'datetime',
];
    public function items(){ return $this->hasMany(OrderItem::class); }
public function customer(){ return $this->belongsTo(Customer::class); }

}
