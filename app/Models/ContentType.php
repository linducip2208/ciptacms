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
    'relation_definitions',
];
protected $casts = [
    'fields' => 'array',
    'relation_definitions' => 'array',
    'is_api_enabled' => 'boolean',
];
    public function records(){ return $this->hasMany(ContentRecord::class,'content_type_id'); }

    /** Relation definitions an operator has configured on this type. */
    public function relationDefinitions(): array
    {
        return array_values((array) ($this->relation_definitions ?? []));
    }

    public function relationNames(): array
    {
        return array_values(array_map(fn (array $r) => (string) ($r['name'] ?? ''), $this->relationDefinitions()));
    }

}
