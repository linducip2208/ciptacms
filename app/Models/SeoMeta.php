<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class SeoMeta extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'seoable_type',
    'seoable_id',
    'meta_title',
    'meta_description',
    'canonical',
    'robots',
    'og_title',
    'og_description',
    'og_image',
    'twitter_card',
    'schema',
];
    public function seoable(){ return $this->morphTo(); }

}
