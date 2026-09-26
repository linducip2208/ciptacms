<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class VendorPayout extends Model
{
    use HasFactory;

protected $fillable = [
    'vendor_id',
    'amount',
    'currency',
    'status',
    'paid_at',
    'notes',
];
protected $casts = [
    'paid_at' => 'datetime',
];


}
