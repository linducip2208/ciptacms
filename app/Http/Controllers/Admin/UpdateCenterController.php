<?php

namespace App\Http\Controllers\Admin;

use App\Core\Services\BackupService;
use App\Core\Services\UpdateService;
use App\Models\Module;
use App\Models\Plugin;
use App\Models\Theme;
use App\Models\UpdateLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class UpdateCenterController extends AdminController
{
    public function index()
    {
        $service = app(UpdateService::class);

        return view('admin.updates.index', [
            'core' => $service->check('core'),
            'modules' => $service->checkAll('module'),
            'plugins' => $service->checkAll('plugin'),
            'themes' => $service->checkAll('theme'),
            'logs' => UpdateLog::latest()->limit(10)->get(),
        ]);
    }

    public function modules()
    {
        return view('admin.updates.registry', [
            'type' => 'module',
            'label' => 'Modules',
            'rows' => app(UpdateService::class)->checkAll('module'),
        ]);
    }

    public function plugins()
    {
        return view('admin.updates.registry', [
            'type' => 'plugin',
            'label' => 'Plugins',
            'rows' => app(UpdateService::class)->checkAll('plugin'),
        ]);
    }

    public function themes()
    {
        return view('admin.updates.registry', [
            'type' => 'theme',
            'label' => 'Themes',
            'rows' => app(UpdateService::class)->checkAll('theme'),
        ]);
    }

    public function history(Request $r)
    {
        $q = UpdateLog::latest();
        if ($type = $r->get('type')) {
            $q->where('type', $type);
        }

        return view('admin.updates.history', [
            'rows' => $q->paginate(30)->withQueryString(),
            'type' => $type,
        ]);
    }

    public function check(Request $r)
    {
        $type = $r->validate(['type' => 'required|in:core,module,plugin,theme'])['type'];
        $service = app(UpdateService::class);

        if ($type === 'core') {
            $result = $service->check('core');
            $message = "Checked: current {$result['current']}, latest {$result['latest']}.";
        } else {
            $results = $service->checkAll($type);
            $message = 'Checked '.count($results)." {$type}(s).";
        }

        $this->audit('check_updates', null, $r);

        return back()->with('ok', $message);
    }

    public function apply(Request $r)
    {
        $data = $r->validate([
            'type' => 'required|in:core,module,plugin,theme',
            'slug' => 'required|string|max:190',
            'to_version' => 'required|string|max:50',
            'confirm' => 'required|accepted',
        ]);

        $this->authorizeUpdate($r, $data['type'], $data['slug']);

        $log = app(UpdateService::class)->apply($data['type'], $data['slug'], $data['to_version']);
        $this->audit('apply_update', $log, $r);

        return back()->with($log->status === 'completed' ? 'ok' : 'error',
            "Update {$log->status}: ".($log->log ?: $data['slug']));
    }

    /**
     * Refuse an update for anything that is not currently registered. Without
     * this an operator could point the updater at an arbitrary module slug.
     */
    protected function authorizeUpdate(Request $r, string $type, string $slug): void
    {
        $models = ['module' => Module::class, 'plugin' => Plugin::class, 'theme' => Theme::class];

        if ($type === 'core') {
            abort_unless($slug === 'lindu', 422, 'The only updatable core package is "lindu".');
            abort_unless($r->user() && $r->user()->hasPermission('settings.manage'), 403, 'You cannot update the core.');

            return;
        }

        abort_unless(isset($models[$type]), 422, 'Unknown update type.');
        $row = $models[$type]::where('slug', $slug)->first();
        abort_unless($row, 404, "No {$type} named '{$slug}' is registered.");
    }
}
