<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Services\SearchService;
use Illuminate\Http\Request;

class SearchApiController extends ApiController
{
    public function __construct(protected SearchService $search) {}

    public function index(Request $r)
    {
        $q = $r->get('q');
        if (! $q) {
            return $this->error('Provide a "q" parameter.', 422);
        }

        $types = $r->get('types')
            ? array_values(array_filter(explode(',', (string) $r->get('types'))))
            : [];

        $results = $this->search->search((string) $q, $types, min(50, max(1, (int) $r->get('per_page', 15))));

        return $this->data($results, ['driver' => $this->search->driverName(), 'query' => $q]);
    }
}
