<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Review extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'reviewable_type',
    'reviewable_id',
    'user_id',
    'rating',
    'title',
    'body',
    'status',
];


}
