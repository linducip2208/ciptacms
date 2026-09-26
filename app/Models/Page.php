<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\SoftDeletes;
    class Page extends Model
    {
        use HasFactory;
        use \App\Core\Traits\Auditable;
        use \App\Core\Traits\HasSlug;
    protected $fillable = [
        'tenant_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'builder',
        'status',
        'published_at',
        'featured_image',
        'meta',
        'template',
        'order',
        'author_id',
    ];
    protected $casts = [
        'builder' => 'array',
        'meta' => 'array',
        'published_at' => 'datetime',
    ];
    public function revisions(){ return $this->hasMany(PageRevision::class); }
public function author(){ return $this->belongsTo(User::class,'author_id'); }
public function seo(){ return $this->morphOne(SeoMeta::class,'seoable'); }


    }
