<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Reservation extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'property_id',
    'room_id',
    'guest_id',
    'number',
    'check_in',
    'check_out',
    'status',
    'total',
    'paid',
    'notes',
];
protected $casts = [
    'check_in' => 'date',
    'check_out' => 'date',
];


}
