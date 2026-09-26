<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class PageRevision extends Model
{
    use HasFactory;

protected $fillable = [
    'page_id',
    'user_id',
    'data',
];
protected $casts = [
    'data' => 'array',
];
    public function page(){ return $this->belongsTo(Page::class); }

}
