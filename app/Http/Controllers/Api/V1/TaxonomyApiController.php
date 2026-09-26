<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;

class TaxonomyApiController extends ApiController
{
    public function categories(Request $r)
    {
        $q = Category::withCount('posts')->orderBy('name');
        if ($search = $r->get('search')) {
            $q->where('name', 'like', "%{$search}%");
        }

        return $this->paginated($q->paginate(min(100, (int) $r->get('per_page', 50))));
    }

    public function tags(Request $r)
    {
        $q = Tag::withCount('posts')->orderBy('name');
        if ($search = $r->get('search')) {
            $q->where('name', 'like', "%{$search}%");
        }

        return $this->paginated($q->paginate(min(100, (int) $r->get('per_page', 50))));
    }
}
