<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class StockMovement extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'product_id',
    'variant_id',
    'type',
    'qty',
    'reference',
    'notes',
];


}
