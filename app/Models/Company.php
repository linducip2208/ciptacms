<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Company extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'name',
    'email',
    'phone',
    'website',
    'address',
    'industry',
];


}
