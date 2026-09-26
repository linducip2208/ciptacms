<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class MemberProfile extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'user_id',
    'display_name',
    'gender',
    'birthdate',
    'city',
    'occupation',
    'education',
    'height',
    'marital_status',
    'bio',
    'interests',
    'photos',
    'is_verified',
    'is_premium',
    'status',
];
protected $casts = [
    'birthdate' => 'date',
    'interests' => 'array',
    'photos' => 'array',
    'is_verified' => 'boolean',
    'is_premium' => 'boolean',
];


}
