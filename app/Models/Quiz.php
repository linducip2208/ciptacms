<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Quiz extends Model
{
    use HasFactory;

protected $fillable = [
    'course_id',
    'lesson_id',
    'title',
    'questions',
    'passing_score',
];
protected $casts = [
    'questions' => 'array',
];


}
