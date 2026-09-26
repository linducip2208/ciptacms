<?php

namespace App\Core\Services;

use App\Models\ContentRecord;
use App\Models\ContentType;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * CSV / JSON import and export for data-builder content types.
 *
 * Imports are row-at-a-time and never abort halfway: every rejected row is
 * reported back to the operator with its line number and reason.
 */
class ImportExportService
{
    public function export(string $model, array $filters = []): array
    {
        $rows = $model::query();
        foreach ($filters as $k => $v) {
            if ($v !== null && $v !== '') {
                $rows->where($k, 'like', "%{$v}%");
            }
        }

        return $rows->limit(5000)->get()->toArray();
    }

    public function toCsv(array $rows): string
    {
        if (! $rows) {
            return '';
        }

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, array_keys($rows[0]));
        foreach ($rows as $r) {
            fputcsv($handle, array_map(
                fn ($v) => is_array($v) ? json_encode($v) : $v,
                array_values($r)
            ));
        }
        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    public function parseCsv(string $content): array
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $this->stripBom($content));
        rewind($handle);

        $headers = [];
        $rows = [];
        $line = 1;

        while (($record = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $line++;
            if ($record === [null] || $record === false) {
                continue;
            }
            $record = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $record);

            if (! $headers) {
                $headers = array_map(fn ($h) => Str::snake((string) $h), $record);

                continue;
            }

            // Skip rows that do not match the header width.
            if (count($record) !== count($headers)) {
                continue;
            }

            $rows[] = array_combine($headers, $record);
        }

        fclose($handle);

        return ['headers' => $headers, 'rows' => $rows];
    }

    protected function stripBom(string $content): string
    {
        return Str::startsWith($content, "\xEF\xBB\xBF") ? substr($content, 3) : $content;
    }

    /**
     * Import rows into a content type.
     *
     * @return array{imported:int,updated:int,failed:int,errors:array<int,array{line:int,reason:string,row:array}>}
     */
    public function import(ContentType $ct, string $path, string $format = 'csv', string $mode = 'insert', ?string $keyField = null): array
    {
        $raw = (string) file_get_contents($path);

        $rows = $format === 'json'
            ? $this->rowsFromJson($raw)
            : $this->parseCsv($raw)['rows'];

        $fields = $ct->fields ?? [];
        $slugs = array_map(fn ($f) => $f['slug'] ?? null, $fields);
        $slugs = array_values(array_filter($slugs));

        $result = ['imported' => 0, 'updated' => 0, 'failed' => 0, 'errors' => []];
        $line = 1;

        foreach ($rows as $row) {
            $line++;

            $data = [];
            foreach ($fields as $field) {
                $slug = $field['slug'] ?? null;
                if (! $slug) {
                    continue;
                }
                $raw = null;
                if (array_key_exists($slug, $row)) {
                    $raw = $row[$slug];
                } elseif (array_key_exists(Str::snake(str_replace(' ', '_', $slug)), $row)) {
                    $raw = $row[Str::snake(str_replace(' ', '_', $slug))];
                }
                $data[$slug] = $this->castValue($raw, (string) ($field['type'] ?? 'text'));
            }

            // Drop unknown columns so a wide export can be re-imported.
            $data = array_intersect_key($data, array_flip($slugs));

            $missing = [];
            foreach ($fields as $field) {
                if (($field['required'] ?? false) && ! isset($data[$field['slug']])) {
                    $missing[] = $field['name'] ?? $field['slug'];
                }
            }
            if ($missing) {
                $result['failed']++;
                $result['errors'][] = ['line' => $line, 'reason' => 'Missing required: '.implode(', ', $missing), 'row' => $row];

                continue;
            }

            try {
                if ($mode === 'update' && $keyField && isset($row[$keyField])) {
                    $existing = $ct->records()->where('data->'.$keyField, $row[$keyField])->first();
                    if ($existing) {
                        $existing->update(['data' => array_merge((array) $existing->data, $data)]);
                        $result['updated']++;

                        continue;
                    }
                }

                $ct->records()->create(['data' => $data, 'status' => $mode === 'insert' ? 'published' : 'draft']);
                $result['imported']++;
            } catch (\Throwable $e) {
                $result['failed']++;
                $result['errors'][] = ['line' => $line, 'reason' => Str::limit($e->getMessage(), 200), 'row' => $row];
            }
        }

        return $result;
    }

    protected function rowsFromJson(string $raw): array
    {
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }
        // Accept either a bare array or {"data": [...]}.
        if (isset($decoded['data']) && is_array($decoded['data'])) {
            $decoded = $decoded['data'];
        }

        $out = [];
        foreach ($decoded as $row) {
            if (is_array($row)) {
                $out[] = $row;
            }
        }

        return $out;
    }

    protected function castValue($value, string $type)
    {
        if ($value === '' || $value === null) {
            return null;
        }

        return match ($type) {
            'number', 'decimal' => is_numeric($value) ? $value + 0 : $value,
            'boolean' => in_array(strtolower((string) $value), ['1', 'true', 'yes', 'y', 'on'], true),
            'multiselect' => is_array($value) ? $value : array_values(array_filter(array_map('trim', explode('|', (string) $value)))),
            'json', 'repeater' => is_array($value) ? $value : (json_decode((string) $value, true) ?: (string) $value),
            default => (string) $value,
        };
    }

    /**
     * Stream a download of content-type records. Fields become the top-level
     * CSV columns so the file round-trips back through import().
     */
    public function download(Collection $records, ContentType $ct, string $format = 'csv')
    {
        $fields = $ct->fields ?? [];
        $filename = $ct->slug.'-'.now()->format('Ymd-His');

        if ($format === 'json') {
            $payload = $records->map(function ($r) {
                $row = (array) $r->data;
                $row['_id'] = $r->id;
                $row['_status'] = $r->status;
                $row['_created_at'] = optional($r->created_at)->toIso8601String();

                return $row;
            })->all();

            return response()->json(['content_type' => $ct->slug, 'count' => count($payload), 'data' => $payload])
                ->header('Content-Disposition', 'attachment; filename="'.$filename.'.json"');
        }

        $handle = fopen('php://temp', 'r+');
        $header = ['_id', '_status', '_created_at'];
        foreach ($fields as $f) {
            $header[] = $f['slug'] ?? $f['name'];
        }
        fputcsv($handle, $header);

        foreach ($records as $r) {
            $line = [$r->id, $r->status, optional($r->created_at)->toIso8601String()];
            foreach ($fields as $f) {
                $v = (array) $r->data;
                $value = $v[$f['slug'] ?? $f['name']] ?? '';
                $line[] = is_array($value) ? implode('|', array_map(fn ($x) => is_scalar($x) ? $x : json_encode($x), $value)) : $value;
            }
            fputcsv($handle, $line);
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
        ]);
    }

    /** Generic model export used by the resource controller. */
    public function downloadModel(string $model, Collection $rows, string $format = 'csv', string $name = 'export')
    {
        $records = $rows->map(fn ($r) => is_array($r) ? $r : $r->toArray())->all();

        if ($format === 'json') {
            return response()->json(['count' => count($records), 'data' => $records])
                ->header('Content-Disposition', 'attachment; filename="'.$name.'.json"');
        }

        return response($this->toCsv($records), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$name.'.csv"',
        ]);
    }
}
