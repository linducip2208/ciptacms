<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Course extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'category_id',
    'instructor_id',
    'title',
    'slug',
    'description',
    'price',
    'level',
    'status',
    'thumbnail',
    'meta',
];
protected $casts = [
    'meta' => 'array',
];
    public function lessons(){ return $this->hasMany(Lesson::class)->orderBy('sort_order'); }

}
