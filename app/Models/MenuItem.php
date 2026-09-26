<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\SoftDeletes;
    class MenuItem extends Model
    {
        use HasFactory;

    protected $fillable = [
        'tenant_id',
        'location',
        'parent_id',
        'title',
        'icon',
        'route',
        'url',
        'permission',
        'roles',
        'module',
        'badge',
        'badge_color',
        'sort_order',
        'target',
        'is_visible',
        'meta',
    ];
    protected $casts = [
        'roles' => 'array',
        'meta' => 'array',
        'is_visible' => 'boolean',
    ];
    public function parent(){ return $this->belongsTo(MenuItem::class,'parent_id'); }
public function children(){ return $this->hasMany(MenuItem::class,'parent_id')->orderBy('sort_order'); }


    }
