<?php

namespace App\View\Composers;

use App\Core\Services\MenuService;
use Illuminate\View\View;

class AdminMenuComposer
{
    public function compose(View $view): void
    {
        $menu = app(MenuService::class)->tree('admin', auth()->user());
        $view->with('adminMenu', $menu);
    }
}