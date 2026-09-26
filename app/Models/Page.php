<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\SoftDeletes;
    class Page extends Model
    {
        use HasFactory, SoftDeletes;
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
        'is_homepage',
        'views',
        'meta_description',
    ];
    protected $casts = [
        'builder' => 'array',
        'meta' => 'array',
        'published_at' => 'datetime',
        'is_homepage' => 'boolean',
        'views' => 'integer',
    ];
    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        $slug = $this->slug ?: 'page-'.$this->getKey();

        return url('/p/'.$slug);
    }

    public function revisions(){ return $this->hasMany(PageRevision::class); }
public function author(){ return $this->belongsTo(User::class,'author_id'); }
public function seo(){ return $this->morphOne(SeoMeta::class,'seoable'); }

    /** Pages that are visible to anonymous visitors right now. */
    public function scopePublished($q)
    {
        return $q->where('status', 'published')
            ->where(function ($w) {
                $w->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function scopeHomepage($q)
    {
        return $q->where('is_homepage', true);
    }

}
