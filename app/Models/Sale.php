<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Sale extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'number',
    'customer_id',
    'cashier_id',
    'subtotal',
    'discount',
    'tax',
    'total',
    'payment_method',
    'amount_paid',
    'change',
    'status',
    'sold_at',
];
protected $casts = [
    'sold_at' => 'datetime',
];
    public function items(){ return $this->hasMany(SaleItem::class); }

}
