<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Supplier extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'name',
    'email',
    'phone',
    'address',
    'meta',
];
protected $casts = [
    'meta' => 'array',
];


}
