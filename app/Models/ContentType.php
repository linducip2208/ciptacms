<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class ContentType extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'name',
    'slug',
    'table_name',
    'description',
    'icon',
    'is_api_enabled',
    'fields',
];
protected $casts = [
    'fields' => 'array',
    'is_api_enabled' => 'boolean',
];
    public function records(){ return $this->hasMany(ContentRecord::class,'content_type_id'); }

}
