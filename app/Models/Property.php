<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Property extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'name',
    'slug',
    'address',
    'city',
    'phone',
    'email',
    'stars',
    'meta',
];
protected $casts = [
    'meta' => 'array',
];
    public function rooms(){ return $this->hasMany(Room::class); }

}
