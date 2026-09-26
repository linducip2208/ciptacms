<?php

namespace App\Core\Services;

use App\Models\ContentRecord;
use App\Models\ContentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Relations between dynamic content records.
 *
 * A relation is defined once on a content type:
 *
 *   { "name": "author", "type": "belongsTo", "target": "people", "label": "Author" }
 *
 * Only ContentRecord is a legal target. The related_type column is
 * polymorphic so a module can point a relation at its own model later, but
 * resolve() only ever hands back whitelisted classes.
 *
 * To-one relations store the related id directly on the record's `data` JSON
 * under the relation name. To-many and many-to-many relations use the
 * content_record_relations pivot, so a single definition works for any number
 * of records.
 */
class RelationRegistry
{
    /** Relation kinds this engine can actually resolve. */
    public const TYPES = [
        'hasOne' => 'Has one',
        'hasMany' => 'Has many',
        'belongsTo' => 'Belongs to',
        'belongsToMany' => 'Belongs to many',
        'morphOne' => 'Morph one',
        'morphMany' => 'Morph many',
    ];

    /**
     * Kinds that keep their links in the pivot table rather than inline in
     * the record's `data` JSON.
     */
    public const PIVOT_TYPES = ['hasMany', 'belongsToMany', 'morphMany'];

    /** Only dynamic records may be a relation target. */
    public function assertTarget(string $slug): ContentType
    {
        $type = ContentType::where('slug', $slug)->first();

        if (! $type) {
            // A model-not-found exception would read as a 404 on an admin form
            // submission; this is a configuration mistake and says so.
            throw new \InvalidArgumentException(
                "No content type named [{$slug}] exists, so a relation cannot point at it."
            );
        }

        return $type;
    }

    /** @return array<int,array<string,mixed>> */
    public function relationsFor(ContentType $ct): array
    {
        return array_values((array) ($ct->relation_definitions ?? []));
    }

    /**
     * @return array<string,mixed> the stored definition
     */
    public function add(ContentType $ct, array $definition): array
    {
        $definition = $this->validate($definition);

        $relations = $this->relationsFor($ct);

        // A relation name must be unique within a content type.
        $relations = array_values(array_filter(
            $relations,
            fn (array $r) => ($r['name'] ?? null) !== $definition['name']
        ));

        $relations[] = $definition;
        $this->sort($ct, $relations);

        $ct->relation_definitions = $relations;
        $ct->save();

        return $definition;
    }

    public function update(ContentType $ct, string $name, array $definition): array
    {
        $definition = $this->validate($definition, $name);

        $relations = array_values(array_map(
            fn (array $r) => ($r['name'] ?? null) === $name ? $definition : $r,
            $this->relationsFor($ct)
        ));

        $this->sort($ct, $relations);
        $ct->relation_definitions = $relations;
        $ct->save();

        return $definition;
    }

    public function remove(ContentType $ct, string $name): void
    {
        $relations = array_values(array_filter(
            $this->relationsFor($ct),
            fn (array $r) => ($r['name'] ?? null) !== $name
        ));

        $this->sort($ct, $relations);
        $ct->relation_definitions = $relations;
        $ct->save();

        $this->purgeLinks($ct, $name);
    }

    protected function sort(ContentType $ct, array $relations): void
    {
        usort($relations, fn ($a, $b) => strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));
    }

    protected function validate(array $d, ?string $forcedName = null): array
    {
        $name = (string) ($forcedName ?? ($d['name'] ?? ''));
        $type = (string) ($d['type'] ?? '');
        $target = (string) ($d['target'] ?? '');

        if (! preg_match('/^[a-z][a-z0-9_]{0,39}$/i', $name)) {
            throw new \InvalidArgumentException(
                'Relation name must start with a letter and contain only letters, numbers and underscores.'
            );
        }

        if (! isset(self::TYPES[$type])) {
            throw new \InvalidArgumentException(
                'Unknown relation type. Use one of: '.implode(', ', array_keys(self::TYPES))
            );
        }

        $this->assertTarget($target);

        return [
            'name' => $name,
            'type' => $type,
            'target' => $target,
            'label' => (string) ($d['label'] ?? Str::headline($name)),
            'field' => (string) ($d['field'] ?? $name),
            'created_at' => now()->toIso8601String(),
        ];
    }

    // ------------------------------------------------------------------
    // Reading and writing links
    // ------------------------------------------------------------------

    /** Attach/detach a target record. */
    public function link(ContentRecord $record, string $relationName, ContentRecord $related): void
    {
        $definition = $this->definitionFor($record->contentType, $relationName);

        if (! $definition) {
            throw new \InvalidArgumentException(
                "Content type [{$record->contentType?->slug}] has no relation [{$relationName}]."
            );
        }

        if (in_array($definition['type'], self::PIVOT_TYPES, true)) {
            // The query builder has no updateOrCreate(), so do it by hand.
            $existing = DB::table('content_record_relations')
                ->where('record_type', ContentRecord::class)
                ->where('record_id', $record->id)
                ->where('related_type', ContentRecord::class)
                ->where('related_id', $related->id)
                ->where('relation', $definition['name'])
                ->first();

            if ($existing) {
                DB::table('content_record_relations')
                    ->where('id', $existing->id)
                    ->update(['updated_at' => now()]);
            } else {
                DB::table('content_record_relations')->insert([
                    'record_type' => ContentRecord::class,
                    'record_id' => $record->id,
                    'related_type' => ContentRecord::class,
                    'related_id' => $related->id,
                    'relation' => $definition['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return;
        }

        // to-one: store on the record's own data
        $data = (array) ($record->data ?? []);
        $data[$definition['name']] = $related->id;
        $record->data = $data;
        $record->save();
    }

    public function unlink(ContentRecord $record, string $relationName, ?ContentRecord $related = null): void
    {
        $definition = $this->definitionFor($record->contentType, $relationName);

        if (! $definition) {
            return;
        }

        if (in_array($definition['type'], self::PIVOT_TYPES, true)) {
            $q = DB::table('content_record_relations')
                ->where('record_type', ContentRecord::class)
                ->where('record_id', $record->id)
                ->where('relation', $definition['name']);

            if ($related) {
                $q->where('related_id', $related->id);
            }

            $q->delete();

            return;
        }

        $data = (array) ($record->data ?? []);
        unset($data[$definition['name']]);
        $record->data = $data;
        $record->save();
    }

    protected function purgeLinks(ContentType $ct, string $name): void
    {
        try {
            DB::table('content_record_relations')
                ->where('record_type', ContentRecord::class)
                ->whereIn('record_id', $ct->records()->pluck('id'))
                ->where('relation', $name)
                ->delete();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    // ------------------------------------------------------------------
    // Resolution
    // ------------------------------------------------------------------

    /** @return array<string,mixed>|null */
    public function definitionFor(?ContentType $ct, string $name): ?array
    {
        if (! $ct) {
            return null;
        }

        foreach ($this->relationsFor($ct) as $r) {
            if (($r['name'] ?? null) === $name) {
                return $r;
            }
        }

        return null;
    }

    /**
     * Resolve one relation on a record.
     *
     * @return \Illuminate\Support\Collection|ContentRecord|null
     */
    public function resolve(ContentRecord $record, string $name)
    {
        $definition = $this->definitionFor($record->contentType, $name);

        if (! $definition) {
            return null;
        }

        $targetType = ContentType::where('slug', $definition['target'])->first();

        if (! $targetType) {
            return collect();
        }

        $pivot = in_array($definition['type'], self::PIVOT_TYPES, true);

        if ($pivot) {
            $ids = DB::table('content_record_relations')
                ->where('record_type', ContentRecord::class)
                ->where('record_id', $record->id)
                ->where('relation', $definition['name'])
                ->orderBy('related_id')
                ->pluck('related_id');

            return ContentRecord::where('content_type_id', $targetType->id)
                ->whereIn('id', $ids)
                ->get();
        }

        // to-one: stored inline on the record's data. Note the parentheses —
        // `(array) $data[$name]` would cast the value at that key, not the
        // whole array, and silently yield an empty array.
        $data = (array) ($record->data ?? []);
        $id = $data[$definition['name']] ?? null;

        if ($id === null || $id === '') {
            return null;
        }

        $found = ContentRecord::where('content_type_id', $targetType->id)->find($id);

        if ($found) {
            return $found;
        }

        // belongsTo may have been stored pointing at the other side.
        $ids = DB::table('content_record_relations')
            ->where('related_type', ContentRecord::class)
            ->where('related_id', $record->id)
            ->where('relation', $definition['name'])
            ->pluck('record_id');

        return ContentRecord::where('content_type_id', $targetType->id)
            ->whereIn('id', $ids)
            ->get();
    }

    /**
     * Attach every resolvable relation to a record's array form.
     *
     * `?include=` on the API turns this on; the admin never pays for it.
     */
    public function withRelations(ContentRecord $record, array $include = []): array
    {
        $out = $record->toArray();

        foreach ($this->relationsFor($record->contentType) as $definition) {
            $name = (string) $definition['name'];

            // Only what was explicitly asked for. An empty $include resolves
            // nothing, so a plain list stays free of extra queries.
            if (! in_array($name, $include, true)) {
                continue;
            }

            $value = $this->resolve($record, $name);

            $out[$name] = $value instanceof Model ? $value->toArray() : collect($value)->toArray();
        }

        return $out;
    }

    /** Relation names exposed by a content type, for the API and docs. */
    public function namesFor(ContentType $ct): array
    {
        return array_values(array_map(fn (array $r) => (string) $r['name'], $this->relationsFor($ct)));
    }
}
