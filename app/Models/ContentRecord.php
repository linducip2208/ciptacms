<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A single record of a dynamic content type.
 *
 * The `data` JSON is the source of truth. Relations live either inline in
 * `data` (to-one) or in the content_record_relations pivot (to-many), and are
 * resolved on demand by App\Core\Services\RelationRegistry — never eagerly,
 * so a list of 50 records does not fire 50 extra queries.
 */
class ContentRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'content_type_id',
        'tenant_id',
        'uuid',
        'data',
        'status',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function contentType()
    {
        return $this->belongsTo(ContentType::class, 'content_type_id');
    }

    /** All records pointing at this one, across every relation name. */
    public function inbound(): MorphMany
    {
        return $this->morphMany(ContentRecord::class, 'related', 'related_type', 'related_id')
            ->where('related_type', ContentRecord::class);
    }

    /** Resolve one relation by name. */
    public function relation(string $name)
    {
        return app(\App\Core\Services\RelationRegistry::class)->resolve($this, $name);
    }

    public function relationNames(): array
    {
        return app(\App\Core\Services\RelationRegistry::class)->namesFor($this->contentType);
    }
}
