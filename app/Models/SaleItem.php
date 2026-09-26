<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class SaleItem extends Model
{
    use HasFactory;

protected $fillable = [
    'sale_id',
    'product_id',
    'name',
    'qty',
    'price',
    'total',
];


}
