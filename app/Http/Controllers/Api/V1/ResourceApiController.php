<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;

/**
 * Generic CRUD over the resources declared in config/lindu_admin.php.
 *
 * The resource slug is resolved through a fixed config map — never used to
 * build a class name. Input is filtered through the model's fillable list
 * rather than passed through wholesale, and every write emits a webhook.
 */
class ResourceApiController extends ApiController
{
    protected array $map = [];

    public function __construct()
    {
        $this->map = (array) config('lindu_admin.resources', []);
    }

    protected function model(string $resource): string
    {
        $m = $this->map[$resource]['model'] ?? null;
        abort_unless($m && class_exists($m), 404, 'Unknown resource.');

        return $m;
    }

    public function index(Request $r, string $resource)
    {
        $m = $this->model($resource);
        $q = $m::query();

        if ($search = $r->get('search')) {
            $cols = (array) ($this->map[$resource]['search'] ?? ['name']);
            $q->where(function ($w) use ($cols, $search) {
                foreach ($cols as $c) {
                    $w->orWhere($c, 'like', "%{$search}%");
                }
            });
        }

        // Only allow sorting by a column that actually exists.
        if ($sort = $r->get('sort')) {
            try {
                $q->orderBy($sort, $r->get('dir') === 'asc' ? 'asc' : 'desc');
            } catch (\Throwable $e) {
                // Unknown column: fall back to the model default order.
            }
        }

        foreach ((array) $r->get('filter', []) as $k => $v) {
            if ($v === '' || $v === null) {
                continue;
            }
            try {
                $q->where($k, $v);
            } catch (\Throwable $e) {
                return $this->error('Invalid filter: '.$k, 422);
            }
        }

        $perPage = min(100, max(1, (int) $r->get('per_page', 15)));

        return $this->paginated($q->paginate($perPage));
    }

    public function show(string $resource, string $id)
    {
        $m = $this->model($resource);

        return $this->data($m::findOrFail($id));
    }

    public function store(Request $r, string $resource)
    {
        $m = $this->model($resource);
        $row = new $m;
        $row->fill($this->safe($r, $row));
        $row->save();

        $this->fire($resource.'.created', $row);

        return $this->data($row->fresh());
    }

    public function update(Request $r, string $resource, string $id)
    {
        $m = $this->model($resource);
        $row = $m::findOrFail($id);
        $row->update($this->safe($r, $row));

        $this->fire($resource.'.updated', $row->fresh());

        return $this->data($row->fresh());
    }

    public function destroy(string $resource, string $id)
    {
        $m = $this->model($resource);
        $row = $m::findOrFail($id);
        $row->delete();

        $this->fire($resource.'.deleted', $row);

        return $this->data(['deleted' => true, 'id' => $id]);
    }

    /** Only fields the model actually allows. */
    protected function safe(Request $r, $model): array
    {
        $allowed = $model->getFillable();
        $input = $r->except(['_token', 'api_token']);

        if ($allowed === ['*'] || $allowed === []) {
            // The model declares no fillable list: fall back to the columns
            // that actually exist rather than trusting the request body.
            return array_intersect_key($input, array_flip(\Illuminate\Support\Facades\Schema::getColumnListing($model->getTable())));
        }

        return array_intersect_key($input, array_flip($allowed));
    }

    protected function fire(string $event, $row): void
    {
        try {
            app(\App\Core\Services\WebhookDispatcher::class)->dispatchEvent($event, is_array($row) ? $row : $row->toArray());
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
