<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class MemberLike extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'from_member_id',
    'to_member_id',
];


}
