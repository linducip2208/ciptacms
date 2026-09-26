<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Lesson extends Model
{
    use HasFactory;

protected $fillable = [
    'course_id',
    'title',
    'slug',
    'content_type',
    'content',
    'video_url',
    'duration_minutes',
    'sort_order',
    'is_free',
];
protected $casts = [
    'is_free' => 'boolean',
];


}
