<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class MemberBlock extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'member_id',
    'blocked_id',
    'reason',
];


}
