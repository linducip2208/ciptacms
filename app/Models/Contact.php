<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Contact extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'company_id',
    'name',
    'email',
    'phone',
    'position',
    'notes',
];


}
