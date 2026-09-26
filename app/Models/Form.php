<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Form extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'settings',
        'is_active',
        'submit_text',
        'submit_label',
        'success_message',
        'status',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * `title` is an alias for `name`.
     *
     * Views, notifications and the API all refer to a form by its title; the
     * column is `name`. Without this, every `{{ $form->title }}` renders
     * blank.
     */
    public function getTitleAttribute(): string
    {
        return (string) $this->name;
    }

    public function getTitleForAttribute(): string
    {
        return (string) $this->name;
    }

    /**
     * submit_label supersedes the older submit_text column.
     *
     * Reads $this->attributes directly: reading $this->submit_label here
     * would re-enter this accessor and recurse.
     */
    public function getSubmitLabelAttribute(): string
    {
        $label = $this->attributes['submit_label'] ?? null;

        if ($label !== null && $label !== '') {
            return (string) $label;
        }

        $fallback = $this->attributes['submit_text'] ?? null;

        return (string) ($fallback !== null && $fallback !== '' ? $fallback : 'Submit');
    }

    public function fields()
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order');
    }

    public function submissions()
    {
        return $this->hasMany(FormSubmission::class);
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }
}
