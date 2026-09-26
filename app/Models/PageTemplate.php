<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class PageTemplate extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'name',
    'slug',
    'blocks',
    'screenshot',
];
protected $casts = [
    'blocks' => 'array',
];


}
