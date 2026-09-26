<?php

namespace App\Http\Controllers\Frontend;

use App\Core\Services\SeoService;
use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function sitemap()
    {
        return response(app(SeoService::class)->sitemap(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    public function robots()
    {
        return response(app(SeoService::class)->robotsTxt(), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
