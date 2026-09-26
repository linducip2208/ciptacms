<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Wishlist extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'user_id',
    'product_id',
];


}
