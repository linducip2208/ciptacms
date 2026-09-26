<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Pipeline extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'name',
    'stages',
];
protected $casts = [
    'stages' => 'array',
];
    public function deals(){ return $this->hasMany(Deal::class); }

}
