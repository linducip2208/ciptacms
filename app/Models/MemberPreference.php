<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class MemberPreference extends Model
{
    use HasFactory;

protected $fillable = [
    'member_id',
    'min_age',
    'max_age',
    'genders',
    'cities',
    'education',
    'meta',
];
protected $casts = [
    'genders' => 'array',
    'cities' => 'array',
    'meta' => 'array',
];


}
