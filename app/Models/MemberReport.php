<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class MemberReport extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'reporter_id',
    'reported_id',
    'reason',
    'details',
    'status',
];


}
