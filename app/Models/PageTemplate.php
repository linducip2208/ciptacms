<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A reusable page layout. `structure` mirrors the page builder payload
 * ({sections: [...]}) so applying a template is a straight assignment.
 */
class PageTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'page_templates';

    protected $fillable = [
        'tenant_id', 'name', 'slug', 'structure', 'blocks',
        'description', 'screenshot', 'thumbnail', 'is_active',
    ];

    protected $casts = [
        'structure' => 'array',
        'blocks' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function ($tpl) {
            if (empty($tpl->slug) && ! empty($tpl->name)) {
                $tpl->slug = Str::slug($tpl->name).'-'.Str::lower(Str::random(4));
            }
            if ($tpl->is_active === null) {
                $tpl->is_active = true;
            }
        });
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    /** Always a {sections: [...]} shape so the builder can consume it. */
    public function structure(): array
    {
        $s = (array) ($this->structure ?: []);

        if (! empty($s['sections'])) {
            return $s;
        }

        if (! empty($s['blocks'])) {
            return ['sections' => [['name' => $this->name, 'layout' => '1-col', 'blocks' => (array) $s['blocks']]]];
        }

        $legacy = (array) ($this->blocks ?: []);

        return $legacy ? ['sections' => [['name' => $this->name, 'layout' => '1-col', 'blocks' => $legacy]]] : ['sections' => []];
    }
}
