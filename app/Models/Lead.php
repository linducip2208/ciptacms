<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Lead extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'name',
    'email',
    'phone',
    'company',
    'source',
    'status',
    'value',
    'assigned_to',
    'notes',
];


}
