<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class ContentRecord extends Model
{
    use HasFactory;

protected $fillable = [
    'content_type_id',
    'tenant_id',
    'uuid',
    'data',
    'status',
];
protected $casts = [
    'data' => 'array',
];


}
