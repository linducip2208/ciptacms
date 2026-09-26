<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Vendor extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'user_id',
    'name',
    'slug',
    'description',
    'logo',
    'status',
    'commission_rate',
    'balance',
];
    public function payouts(){ return $this->hasMany(VendorPayout::class); }

}
