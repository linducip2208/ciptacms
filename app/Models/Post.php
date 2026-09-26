<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\SoftDeletes;
    class Post extends Model
    {
        use HasFactory, SoftDeletes;
        use \App\Core\Traits\Auditable;
    protected $fillable = [
        'tenant_id',
        'category_id',
        'author_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'status',
        'published_at',
        'featured_image',
        'meta',
        'views',
    ];
    protected $casts = [
        'meta' => 'array',
        'published_at' => 'datetime',
    ];
    public function category(){ return $this->belongsTo(Category::class); }
public function tags(){ return $this->belongsToMany(Tag::class,'post_tag'); }
public function author(){ return $this->belongsTo(User::class,'author_id'); }
public function comments(){ return $this->morphMany(Comment::class,'commentable'); }
public function seo(){ return $this->morphOne(SeoMeta::class,'seoable'); }


    }
