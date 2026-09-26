<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Services\DataBuilderService;
use App\Models\ContentType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Data builder over the API.
 *
 * Only content types with is_api_enabled are exposed. Record writes go
 * through the same validation rules as the admin UI so the API cannot
 * bypass them.
 */
class ContentTypeApiController extends ApiController
{
    public function __construct(protected DataBuilderService $builder) {}

    protected function type(string $slug): ContentType
    {
        $ct = ContentType::where('slug', $slug)->where('is_api_enabled', true)->first();

        abort_if(! $ct, 404, 'No API-enabled content type with that slug.');

        return $ct;
    }

    public function index()
    {
        return $this->data(
            ContentType::where('is_api_enabled', true)
                ->withCount('records')
                ->orderBy('name')
                ->get()
                ->map(fn ($ct) => [
                    'id' => $ct->id,
                    'name' => $ct->name,
                    'slug' => $ct->slug,
                    'description' => $ct->description,
                    'fields' => $this->fieldSchema($ct),
                    'records_count' => $ct->records_count,
                ])
        );
    }

    public function show(string $slug)
    {
        $ct = $this->type($slug);

        return $this->data([
            'id' => $ct->id,
            'name' => $ct->name,
            'slug' => $ct->slug,
            'description' => $ct->description,
            'fields' => $this->fieldSchema($ct),
        ]);
    }

    public function records(Request $r, string $slug)
    {
        $ct = $this->type($slug);
        $q = $ct->records();

        if ($status = $r->get('status')) {
            $q->where('status', $status);
        }
        if ($search = $r->get('search')) {
            $first = $ct->fields[0]['slug'] ?? null;
            if ($first) {
                $q->where('data->'.$first, 'like', "%{$search}%");
            }
        }

        $sort = $r->get('sort', 'id');
        $q->orderBy(in_array($sort, ['id', 'created_at', 'updated_at', 'status'], true) ? $sort : 'id',
            $r->get('dir') === 'asc' ? 'asc' : 'desc');

        $perPage = min(100, max(1, (int) $r->get('per_page', 15)));

        return $this->paginated($q->paginate($perPage));
    }

    public function showRecord(string $slug, $id)
    {
        $ct = $this->type($slug);

        return $this->data($this->present($ct, $ct->records()->findOrFail($id)));
    }

    public function storeRecord(Request $r, string $slug)
    {
        $ct = $this->type($slug);
        $data = $this->validateData($r, $ct);

        $record = $ct->records()->create($data);
        $this->mirror($ct, $record);
        $this->fire('record.created', $ct, $record);

        return $this->data($this->present($ct, $record->fresh()), [], 201);
    }

    public function updateRecord(Request $r, string $slug, $id)
    {
        $ct = $this->type($slug);
        $record = $ct->records()->findOrFail($id);
        $record->update($this->validateData($r, $ct, $record->id));
        $this->mirror($ct, $record);
        $this->fire('record.updated', $ct, $record);

        return $this->data($this->present($ct, $record->fresh()));
    }

    public function destroyRecord(string $slug, $id)
    {
        $ct = $this->type($slug);
        $record = $ct->records()->findOrFail($id);
        $record->delete();
        $this->fire('record.deleted', $ct, $record);

        return $this->data(['deleted' => true, 'id' => $id]);
    }

    // ---- helpers ------------------------------------------------------

    protected function fieldSchema(ContentType $ct): array
    {
        $out = [];
        foreach ($ct->fields ?? [] as $f) {
            $out[] = [
                'slug' => $f['slug'] ?? null,
                'label' => $f['name'] ?? ($f['label'] ?? null),
                'type' => $f['type'] ?? 'text',
                'required' => (bool) ($f['required'] ?? false),
                'unique' => (bool) ($f['unique'] ?? false),
                'options' => $f['options'] ?? null,
                'help' => $f['help'] ?? null,
            ];
        }

        return $out;
    }

    protected function validateData(Request $r, ContentType $ct, $ignoreId = null): array
    {
        $input = (array) $r->input('data', $r->except(['data', '_token']));
        $errors = [];
        $out = [];

        foreach ($ct->fields ?? [] as $field) {
            $key = $field['slug'] ?? null;
            if (! $key) {
                continue;
            }

            $rule = ($field['required'] ?? false) ? 'required' : 'nullable';
            $rule .= match ($field['type'] ?? 'text') {
                'number', 'decimal' => '|numeric',
                'boolean' => '|boolean',
                'multiselect', 'json', 'repeater' => '|array',
                'email' => '|email|max:190',
                'url' => '|url|max:500',
                'date', 'datetime' => '|date',
                default => '|string|max:20000',
            };
            if ($field['unique'] ?? false) {
                $rule .= '|unique:content_records,data->'.$key.($ignoreId ? ','.$ignoreId : '');
            }

            $v = \Illuminate\Support\Facades\Validator::make([$key => $input[$key] ?? null], [$key => $rule]);
            if ($v->fails()) {
                $errors[$key] = $v->errors()->get($key);
            } else {
                $out[$key] = $v->validated()[$key];
            }
        }

        if ($errors) {
            abort(response()->json(['message' => 'Validation failed.', 'errors' => $errors], 422));
        }

        return [
            'data' => $out,
            'status' => $r->input('status', 'published'),
        ];
    }

    protected function present(ContentType $ct, $record): array
    {
        return [
            'id' => $record->id,
            'uuid' => $record->uuid,
            'content_type' => $ct->slug,
            'data' => $record->data,
            'status' => $record->status,
            'created_at' => optional($record->created_at)->toIso8601String(),
            'updated_at' => optional($record->updated_at)->toIso8601String(),
        ];
    }

    protected function mirror(ContentType $ct, $record): void
    {
        try {
            $this->builder->syncRecord($ct, $record);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function fire(string $event, ContentType $ct, $record): void
    {
        try {
            $payload = ['content_type' => $ct->slug, 'record' => $record->toArray()];
            event('record.'.$event, $payload);
            app(\App\Core\Services\WebhookDispatcher::class)->dispatchEvent('record.'.$event, $payload);
            app(\App\Core\Services\WorkflowEngine::class)->trigger('record.'.$event, [
                'content_type' => $ct->slug,
                'data' => $record->data,
                'id' => $record->id,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
