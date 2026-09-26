<?php
    namespace App\Models;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\SoftDeletes;
    class Product extends Model
    {
        use HasFactory;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'vendor_id',
        'name',
        'slug',
        'sku',
        'description',
        'price',
        'cost',
        'stock',
        'track_stock',
        'is_active',
        'attributes',
        'images',
        'weight',
        'tax_rate',
    ];
    protected $casts = [
        'attributes' => 'array',
        'images' => 'array',
        'is_active' => 'boolean',
        'track_stock' => 'boolean',
    ];
    public function category(){ return $this->belongsTo(ProductCategory::class,'category_id'); }
public function variants(){ return $this->hasMany(ProductVariant::class); }
public function reviews(){ return $this->morphMany(Review::class,'reviewable'); }


    }
