<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A saved block the page builder can drop into any page.
 *
 * `data` holds the component payload (type, heading, image, …) matching the
 * BlockLibrary field definitions. The legacy `blocks` column is left in place
 * for compatibility with rows created before the builder rewrite.
 */
class ReusableBlock extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'reusable_blocks';

    protected $fillable = [
        'tenant_id', 'name', 'slug', 'data', 'blocks', 'is_global', 'is_active',
    ];

    protected $casts = [
        'data' => 'array',
        'blocks' => 'array',
        'is_global' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function ($block) {
            if (empty($block->slug) && ! empty($block->name)) {
                $block->slug = Str::slug($block->name).'-'.Str::lower(Str::random(4));
            }
            if ($block->is_active === null) {
                $block->is_active = true;
            }
        });
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeGlobal($q)
    {
        return $q->where('is_global', true);
    }

    /**
     * The component type lives inside the JSON payload — the table has no
     * `type` column, so exposing it here keeps the callers simple.
     */
    public function getTypeAttribute(): ?string
    {
        $data = (array) ($this->data ?: []);

        return isset($data['type']) ? (string) $data['type'] : null;
    }

    /** The payload to hand back to the builder, always including the type. */
    public function payload(): array
    {
        $data = (array) ($this->data ?: []);

        if (! $data) {
            $data = (array) ($this->blocks ?: []);
        }

        $type = $this->getTypeAttribute();
        if ($type !== null) {
            $data['type'] = $type;
        }

        return $data;
    }
}
