<?php

namespace App\Core\Services;

use App\Models\ContentRecord;
use App\Models\ContentType;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Dynamic content types.
 *
 * A content type always has a JSON record in `content_records`. When
 * `table_name` is set the same values are mirrored into a real physical
 * table so operators can report on them with plain SQL. The JSON record is
 * the source of truth; a failure to mirror never fails a request.
 */
class DataBuilderService
{
    /** Field type → physical column definition. */
    public const COLUMN_MAP = [
        'text' => ['column' => 'string', 'length' => 255],
        'longtext' => ['column' => 'text'],
        'richtext' => ['column' => 'text'],
        'email' => ['column' => 'string', 'length' => 190],
        'url' => ['column' => 'string', 'length' => 500],
        'image' => ['column' => 'string', 'length' => 500],
        'file' => ['column' => 'string', 'length' => 500],
        'select' => ['column' => 'string', 'length' => 255],
        'number' => ['column' => 'integer'],
        'decimal' => ['column' => 'decimal', 'precision' => 15, 'scale' => 2],
        'boolean' => ['column' => 'boolean'],
        'date' => ['column' => 'date'],
        'datetime' => ['column' => 'dateTime'],
        'multiselect' => ['column' => 'json'],
        'json' => ['column' => 'json'],
        'repeater' => ['column' => 'json'],
        'relation' => ['column' => 'string', 'length' => 190],
    ];

    public function fieldMap(string $type): string
    {
        return self::COLUMN_MAP[$type]['column'] ?? 'text';
    }

    /** Backwards-compatible entry point used by existing callers. */
    public function createPhysicalTable(ContentType $ct): void
    {
        $this->syncPhysicalTable($ct);
    }

    public function safeTableName(ContentType $ct): string
    {
        if (! empty($ct->table_name)) {
            return $ct->table_name;
        }
        $base = Str::of($ct->slug)->lower()->replaceMatches('/[^a-z0-9_]+/', '_')->substr(0, 40)->toString();
        $base = trim($base, '_') ?: 'content';

        return 'cb_'.$base;
    }

    /** Create the physical table and/or add any newly declared columns. */
    public function syncPhysicalTable(ContentType $ct): ?string
    {
        $table = $this->safeTableName($ct);

        $created = false;
        if (! Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->uuid('uuid')->nullable()->unique();
                $t->string('tenant_id')->nullable()->index();
                $t->timestamps();
                $t->softDeletes();
            });
            $created = true;
        }

        foreach ($ct->fields ?? [] as $field) {
            $col = $this->columnName($field);
            if (! $col || Schema::hasColumn($table, $col)) {
                continue;
            }
            try {
                Schema::table($table, function (Blueprint $t) use ($field, $col) {
                    $this->defineColumn($t, $col, (string) ($field['type'] ?? 'text'));
                });
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $ct->table_name = $table;
        if (! $ct->isDirty()) {
            $ct->save();
        }

        return $created ? $table : null;
    }

    protected function columnName(array $field): ?string
    {
        $col = $field['slug'] ?? $field['name'] ?? null;
        if (! $col) {
            return null;
        }
        $col = Str::snake((string) $col);
        if (! preg_match('/^[a-z][a-z0-9_]*$/', $col)) {
            return null;
        }

        return $col;
    }

    protected function defineColumn(Blueprint $t, string $col, string $type): void
    {
        $def = self::COLUMN_MAP[$type] ?? ['column' => 'text'];

        match ($def['column']) {
            'string' => $t->string($col, $def['length'] ?? 255)->nullable(),
            'integer' => $t->integer($col)->nullable(),
            'decimal' => $t->decimal($col, $def['precision'] ?? 15, $def['scale'] ?? 2)->nullable(),
            'boolean' => $t->boolean($col)->default(false),
            'date' => $t->date($col)->nullable(),
            'dateTime' => $t->dateTime($col)->nullable(),
            'json' => $t->json($col)->nullable(),
            default => $t->text($col)->nullable(),
        };
    }

    public function removeColumn(ContentType $ct, string $slug): void
    {
        $table = $this->safeTableName($ct);
        $col = Str::snake($slug);

        if (! Schema::hasTable($table) || ! preg_match('/^[a-z][a-z0-9_]*$/', $col)) {
            return;
        }
        if (! Schema::hasColumn($table, $col)) {
            return;
        }

        try {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn($col));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function dropPhysicalTable(ContentType $ct): void
    {
        $table = $this->safeTableName($ct);

        // Never drop a table outside the builder's own namespace.
        if (! str_starts_with($table, 'cb_') || ! Schema::hasTable($table)) {
            return;
        }

        Schema::drop($table);
    }

    /** Mirror one JSON record into the physical table. */
    public function syncRecord(ContentType $ct, ContentRecord $record): void
    {
        $table = $this->safeTableName($ct);
        if (! Schema::hasTable($table)) {
            return;
        }

        $row = ['uuid' => $record->uuid, 'tenant_id' => $record->tenant_id];
        $data = (array) ($record->data ?? []);

        foreach ($ct->fields ?? [] as $field) {
            $col = $this->columnName($field);
            if (! $col) {
                continue;
            }
            $value = $data[$field['slug'] ?? ''] ?? null;

            if (in_array($field['type'] ?? '', ['multiselect', 'json', 'repeater'], true)) {
                $value = is_array($value) ? json_encode($value) : $value;
            }
            if (($field['type'] ?? '') === 'boolean') {
                $value = $value ? 1 : 0;
            }

            $row[$col] = $value;
        }

        try {
            $exists = DB::table($table)->where('id', $record->id)->exists();
            if ($exists) {
                DB::table($table)->where('id', $record->id)->update($row);
            } else {
                DB::table($table)->insert($row + ['id' => $record->id]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Physical rows keyed by id, for reporting. */
    public function physicalRows(ContentType $ct, int $limit = 100): array
    {
        $table = $this->safeTableName($ct);
        if (! Schema::hasTable($table)) {
            return [];
        }

        return DB::table($table)->orderByDesc('id')->limit($limit)->get()->all();
    }
}
