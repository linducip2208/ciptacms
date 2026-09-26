<?php

namespace App\Http\Controllers\Admin;

use App\Core\Services\DataBuilderService;
use App\Core\Services\ImportExportService;
use App\Models\ContentRecord;
use App\Models\ContentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DataBuilderController extends AdminController
{
    public const FIELD_TYPES = [
        'text' => ['label' => 'Text', 'column' => 'text', 'kind' => 'string'],
        'longtext' => ['label' => 'Long text', 'column' => 'text', 'kind' => 'string'],
        'richtext' => ['label' => 'Rich text', 'column' => 'text', 'kind' => 'string'],
        'number' => ['label' => 'Number', 'column' => 'integer', 'kind' => 'number'],
        'decimal' => ['label' => 'Decimal', 'column' => 'decimal', 'kind' => 'number'],
        'boolean' => ['label' => 'Checkbox', 'column' => 'boolean', 'kind' => 'boolean'],
        'date' => ['label' => 'Date', 'column' => 'date', 'kind' => 'string'],
        'datetime' => ['label' => 'Date & time', 'column' => 'dateTime', 'kind' => 'string'],
        'email' => ['label' => 'Email', 'column' => 'string', 'kind' => 'string'],
        'url' => ['label' => 'URL', 'column' => 'string', 'kind' => 'string'],
        'image' => ['label' => 'Image', 'column' => 'string', 'kind' => 'string'],
        'file' => ['label' => 'File', 'column' => 'string', 'kind' => 'string'],
        'select' => ['label' => 'Select', 'column' => 'string', 'kind' => 'string'],
        'multiselect' => ['label' => 'Multi-select', 'column' => 'json', 'kind' => 'array'],
        'json' => ['label' => 'JSON', 'column' => 'json', 'kind' => 'object'],
        'relation' => ['label' => 'Relation', 'column' => 'string', 'kind' => 'string'],
        'repeater' => ['label' => 'Repeater', 'column' => 'json', 'kind' => 'object'],
    ];

    public const RELATION_TYPES = ['hasOne', 'hasMany', 'belongsTo', 'belongsToMany', 'morphOne', 'morphMany'];

    // ---- Content types -------------------------------------------------

    public function index()
    {
        return view('admin.data.content-types', [
            'rows' => ContentType::withCount('records')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.data.content-type-form', [
            'row' => new ContentType,
            'fieldTypes' => self::FIELD_TYPES,
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|max:190',
            'slug' => 'required|string|max:190',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'is_api_enabled' => 'nullable|boolean',
        ]);

        $data['slug'] = Str::slug($data['slug'], '_');
        $data['is_api_enabled'] = $r->boolean('is_api_enabled', true);

        $ct = ContentType::create($data);
        $ct->table_name = $this->safeTableName($data['slug']);
        $ct->save();

        $ct->fields = [];
        $ct->save();

        $this->audit('create_content_type', $ct, $r);

        return redirect()->route('admin.cms.types.edit', $ct)->with('ok', 'Content type created. Add fields next.');
    }

    public function edit(ContentType $contentType)
    {
        return view('admin.data.content-type-form', [
            'row' => $contentType,
            'fieldTypes' => self::FIELD_TYPES,
            'records' => $contentType->records()->latest()->limit(10)->get(),
        ]);
    }

    public function update(Request $r, ContentType $contentType)
    {
        $data = $r->validate([
            'name' => 'required|string|max:190',
            'slug' => 'required|string|max:190',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'is_api_enabled' => 'nullable|boolean',
        ]);

        $data['slug'] = Str::slug($data['slug'], '_');
        $data['is_api_enabled'] = $r->boolean('is_api_enabled');
        $contentType->update($data);

        $this->audit('update_content_type', $contentType, $r);

        return back()->with('ok', 'Content type updated');
    }

    public function destroy(Request $r, ContentType $contentType)
    {
        if ($contentType->records()->exists()) {
            return back()->withErrors(['msg' => 'Delete or archive the records first.']);
        }

        $service = app(DataBuilderService::class);
        $service->dropPhysicalTable($contentType);
        $contentType->delete();
        $this->audit('delete_content_type', $contentType, $r);

        return back()->with('ok', 'Content type deleted');
    }

    public function fields()
    {
        return view('admin.data.fields', [
            'types' => ContentType::orderBy('name')->get(),
            'fieldTypes' => self::FIELD_TYPES,
            'relations' => self::RELATION_TYPES,
        ]);
    }

    public function relations()
    {
        return view('admin.data.relations', [
            'types' => ContentType::orderBy('name')->get(),
            'relationTypes' => self::RELATION_TYPES,
        ]);
    }

    public function storeField(Request $r, ContentType $contentType)
    {
        $data = $r->validate([
            'name' => 'required|string|max:190',
            'slug' => 'required|string|max:190',
            'type' => 'required|in:'.implode(',', array_keys(self::FIELD_TYPES)),
            'required' => 'nullable|boolean',
            'unique' => 'nullable|boolean',
            'options' => 'nullable',
            'help' => 'nullable|string',
            'default' => 'nullable',
        ]);

        $field = [
            'name' => $data['name'],
            'slug' => Str::snake($data['slug']),
            'type' => $data['type'],
            'label' => $data['name'],
            'required' => $r->boolean('required'),
            'unique' => $r->boolean('unique'),
            'options' => $this->parseOptions($data['options'] ?? null),
            'help' => $data['help'] ?? null,
            'default' => $data['default'] ?? null,
        ];

        $fields = $contentType->fields ?? [];
        $fields[] = $field;
        $contentType->fields = $fields;
        $contentType->save();

        app(DataBuilderService::class)->syncPhysicalTable($contentType);
        $this->audit('create_field', $contentType, $r);

        return back()->with('ok', "Field '{$field['name']}' added");
    }

    public function updateField(Request $r, ContentType $contentType, int $field)
    {
        $data = $r->validate([
            'name' => 'required|string|max:190',
            'slug' => 'required|string|max:190',
            'type' => 'required|in:'.implode(',', array_keys(self::FIELD_TYPES)),
            'required' => 'nullable|boolean',
            'unique' => 'nullable|boolean',
            'options' => 'nullable',
            'help' => 'nullable|string',
            'default' => 'nullable',
        ]);

        $fields = $contentType->fields ?? [];
        if (! isset($fields[$field])) {
            abort(404, 'Unknown field index.');
        }

        $fields[$field] = array_merge($fields[$field], [
            'name' => $data['name'],
            'slug' => Str::snake($data['slug']),
            'label' => $data['name'],
            'type' => $data['type'],
            'required' => $r->boolean('required'),
            'unique' => $r->boolean('unique'),
            'options' => $this->parseOptions($data['options'] ?? null),
            'help' => $data['help'] ?? null,
            'default' => $data['default'] ?? null,
        ]);

        $contentType->fields = $fields;
        $contentType->save();

        app(DataBuilderService::class)->syncPhysicalTable($contentType);
        $this->audit('update_field', $contentType, $r);

        return back()->with('ok', 'Field updated');
    }

    public function destroyField(Request $r, ContentType $contentType, int $field)
    {
        $fields = $contentType->fields ?? [];
        if (! isset($fields[$field])) {
            abort(404, 'Unknown field index.');
        }

        $removed = $fields[$field];
        unset($fields[$field]);
        $contentType->fields = array_values($fields);
        $contentType->save();

        app(DataBuilderService::class)->removeColumn($contentType, (string) $removed['slug']);
        $this->audit('delete_field', $contentType, $r);

        return back()->with('ok', "Field '{$removed['name']}' removed");
    }

    // ---- Records -------------------------------------------------------

    public function records()
    {
        return view('admin.data.records', [
            'types' => ContentType::withCount('records')->orderBy('name')->get(),
        ]);
    }

    public function recordList(Request $r, ContentType $contentType)
    {
        $q = $contentType->records();
        if ($status = $r->get('status')) {
            $q->where('status', $status);
        }
        if ($search = $r->get('search')) {
            $fields = $contentType->fields ?? [];
            $primary = $fields[0]['slug'] ?? null;
            if ($primary) {
                $q->where('data->'.$primary, 'like', "%{$search}%");
            }
        }

        return view('admin.data.record-list', [
            'ct' => $contentType,
            'rows' => $q->latest()->paginate(25)->withQueryString(),
            'columns' => array_slice($contentType->fields ?? [], 0, 5),
        ]);
    }

    public function recordForm(Request $r, ContentType $contentType, $id = null)
    {
        $record = $id ? $contentType->records()->findOrFail($id) : new ContentRecord;

        return view('admin.data.record-form', [
            'ct' => $contentType,
            'record' => $record,
            'action' => $id
                ? route('admin.cms.records.update', [$contentType, $id])
                : route('admin.cms.records.store', $contentType),
        ]);
    }

    public function storeRecord(Request $r, ContentType $contentType)
    {
        $data = $this->validateRecord($r, $contentType);
        $record = $contentType->records()->create($data);
        $this->syncPhysical($contentType, $record);
        $this->afterRecord('created', $contentType, $record, $r);

        return back()->with('ok', 'Record created');
    }

    public function updateRecord(Request $r, ContentType $contentType, $id)
    {
        $record = $contentType->records()->findOrFail($id);
        $record->update($this->validateRecord($r, $contentType));
        $this->syncPhysical($contentType, $record);
        $this->afterRecord('updated', $contentType, $record, $r);

        return back()->with('ok', 'Record updated');
    }

    public function destroyRecord(Request $r, ContentType $contentType, $id)
    {
        $record = $contentType->records()->findOrFail($id);
        $record->delete();
        $this->afterRecord('deleted', $contentType, $record, $r);

        return back()->with('ok', 'Record moved to trash');
    }

    public function bulkRecords(Request $r, ContentType $contentType)
    {
        $data = $r->validate([
            'action' => 'required|in:publish,draft,delete',
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $q = $contentType->records()->whereIn('id', $data['ids']);
        $count = match ($data['action']) {
            'publish' => $q->update(['status' => 'published']),
            'draft' => $q->update(['status' => 'draft']),
            'delete' => $q->delete(),
        };

        $this->audit('bulk_'.$data['action'].'_records', $contentType, $r);

        return back()->with('ok', "{$count} record(s) updated");
    }

    // ---- Import / export ------------------------------------------------

    public function importForm()
    {
        return view('admin.data.import', [
            'types' => ContentType::orderBy('name')->get(),
        ]);
    }

    public function import(Request $r)
    {
        $data = $r->validate([
            'content_type_id' => 'required|exists:content_types,id',
            'file' => 'required|file|max:20480',
            'format' => 'required|in:csv,json',
            'mode' => 'required|in:insert,update',
            'key_field' => 'nullable|string|max:100',
        ]);

        $ct = ContentType::findOrFail($data['content_type_id']);
        $path = $r->file('file')->getRealPath();

        $result = app(ImportExportService::class)->import($ct, $path, $data['format'], $data['mode'], $data['key_field'] ?: null);

        $this->audit('import_records', $ct, $r);

        return back()->with('ok', "Imported {$result['imported']} row(s), {$result['failed']} failed.")
            ->with('import_errors', $result['errors']);
    }

    public function exportForm()
    {
        return view('admin.data.export', [
            'types' => ContentType::orderBy('name')->get(),
        ]);
    }

    public function exportRun(Request $r)
    {
        $data = $r->validate([
            'content_type_id' => 'required|exists:content_types,id',
            'format' => 'required|in:csv,json',
            'status' => 'nullable|in:published,draft',
        ]);

        $ct = ContentType::findOrFail($data['content_type_id']);
        $q = $ct->records();
        if ($data['status'] ?? null) {
            $q->where('status', $data['status']);
        }

        $this->audit('export_records', $ct, $r);

        return app(ImportExportService::class)->download($q->get(), $ct, $data['format']);
    }

    // ---- helpers --------------------------------------------------------

    protected function validateRecord(Request $r, ContentType $ct): array
    {
        $all = $r->input('data', []);
        $errors = [];
        $validated = [];
        $recordId = $r->route('id');

        foreach ($ct->fields ?? [] as $field) {
            $type = self::FIELD_TYPES[$field['type']] ?? ['kind' => 'string'];
            $rule = ($field['required'] ?? false) ? 'required' : 'nullable';

            $rule .= match ($type['kind']) {
                'number' => '|numeric',
                'boolean' => '|boolean',
                'array', 'object' => '|array',
                default => '|string',
            };

            if ($field['type'] === 'email') {
                $rule .= '|email';
            }
            if ($field['type'] === 'url') {
                $rule .= '|url';
            }
            if ($field['type'] === 'date' || $field['type'] === 'datetime') {
                $rule .= '|date';
            }
            if ($field['unique'] ?? false) {
                $rule .= '|unique:content_records,data->'.$field['slug'].($recordId ? ','.$recordId : '');
            }

            $value = $all[$field['slug']] ?? null;
            if ($type['kind'] === 'boolean') {
                $value = $r->boolean('data['.$field['slug'].']', false);
            }

            $v = \Illuminate\Support\Facades\Validator::make([$field['slug'] => $value], [$field['slug'] => $rule]);
            if ($v->fails()) {
                $errors[$field['slug']] = $v->errors()->get($field['slug']);
            } else {
                $validated[$field['slug']] = $v->validated()[$field['slug']];
            }
        }

        if ($errors) {
            throw new \Illuminate\Validation\ValidationException($r, $errors);
        }

        return [
            'data' => $validated,
            'status' => $r->input('status', 'published'),
        ];
    }

    protected function syncPhysical(ContentType $ct, ContentRecord $record): void
    {
        try {
            app(DataBuilderService::class)->syncRecord($ct, $record);
        } catch (\Throwable $e) {
            // The JSON record is the source of truth; the physical table is a
            // convenience mirror for reporting. Do not fail the request.
            report($e);
        }
    }

    protected function afterRecord(string $action, ContentType $ct, ContentRecord $record, Request $r): void
    {
        $this->audit($action.'_record', $record, $r);

        try {
            event('record.'.$action, ['type' => $ct->slug, 'record' => $record->toArray()]);
            app(\App\Core\Services\WebhookDispatcher::class)->dispatchEvent('record.'.$action, [
                'content_type' => $ct->slug,
                'record' => $record->toArray(),
            ]);
            app(\App\Core\Services\WorkflowEngine::class)->trigger('record.'.$action, [
                'content_type' => $ct->slug,
                'data' => $record->data,
                'id' => $record->id,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function safeTableName(string $slug): string
    {
        $base = Str::of($slug)->lower()->replaceMatches('/[^a-z0-9_]+/', '_')->substr(0, 40)->toString();
        $base = trim($base, '_') ?: 'content';

        return 'cb_'.$base;
    }

    protected function parseOptions($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (! $value) {
            return [];
        }
        $decoded = json_decode((string) $value, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $out = [];
        foreach (array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value))) as $line) {
            if (str_contains($line, '|')) {
                [$k, $v] = array_pad(explode('|', $line, 2), 2, '');
                $out[trim($k)] = trim($v);
            } else {
                $out[$line] = $line;
            }
        }

        return $out;
    }
}
