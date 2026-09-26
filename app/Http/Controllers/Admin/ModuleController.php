<?php

namespace App\Http\Controllers\Admin;

use App\Core\Services\ModuleManager;
use App\Models\Module;
use Illuminate\Http\Request;

class ModuleController extends AdminController
{
    /**
     * The only ModuleManager methods reachable from the URL.
     *
     * Without this allowlist `$m->$action($slug)` would call any public
     * method on the service using an attacker-supplied name.
     */
    public const ACTIONS = ['install', 'activate', 'deactivate', 'uninstall'];

    public function index(ModuleManager $m, Request $r)
    {
        $m->syncRegistry();

        $q = Module::orderBy('name');
        match ($r->get('filter')) {
            'installed' => $q->where('is_installed', true),
            'active' => $q->where('is_active', true),
            'inactive' => $q->where('is_installed', true)->where('is_active', false),
            default => null,
        };

        return view('admin.modules.index', [
            'modules' => $q->get(),
            'filter' => $r->get('filter'),
        ]);
    }

    public function action(Request $r, string $slug, string $action, ModuleManager $m)
    {
        abort_unless(
            in_array($action, self::ACTIONS, true),
            404,
            'Unknown module action.'
        );

        abort_unless(
            $r->user()?->hasPermission('modules.manage'),
            403,
            'You do not have permission to manage modules.'
        );

        try {
            $m->$action($slug);

            return back()->with('ok', ucfirst($action).'d: '.$slug);
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
    }
}
