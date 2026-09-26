<?php

namespace App\Http\Controllers\Admin;

use App\Core\Plugins\PluginException;
use App\Core\Plugins\UnknownPluginException;
use App\Core\Services\PluginManager;
use App\Models\Plugin;
use Illuminate\Http\Request;

class PluginController extends AdminController
{
    public function index(PluginManager $m, Request $r)
    {
        $m->syncRegistry();

        $q = Plugin::orderBy('name');
        $filter = $r->get('filter');
        if ($filter === 'installed') {
            $q->where('is_installed', true);
        } elseif ($filter === 'active') {
            $q->where('is_active', true);
        } elseif ($filter === 'inactive') {
            $q->where('is_installed', true)->where('is_active', false);
        }

        return view('admin.plugins.index', [
            'plugins' => $q->get(),
            'filter' => $filter,
        ]);
    }

    /**
     * POST /admin/plugins/{slug}/{action}
     *
     * There is no dynamic method call here. `{action}` is matched against the
     * five lifecycle verbs by name, and anything else is treated as a
     * plugin-declared action key that PluginManager::callAction() resolves
     * through the plugin's own manifest — so a URL can never name a method.
     */
    public function action(string $slug, string $action, PluginManager $m)
    {
        if (! in_array($action, PluginManager::ACTIONS, true)) {
            return $this->pluginAction($slug, $action, $m);
        }

        try {
            match ($action) {
                'install' => $m->install($slug),
                'activate' => $m->activate($slug),
                'deactivate' => $m->deactivate($slug),
                'uninstall' => $m->uninstall($slug),
                'update' => $m->update($slug),
            };

            return back()->with('ok', $this->verb($action).$slug);
        } catch (UnknownPluginException|PluginException) {
            // An unresolvable slug is a 404, not a 500 with a class name in it.
            abort(404);
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    /** A plugin-declared action, or 404 when the manifest does not allow it. */
    protected function pluginAction(string $slug, string $action, PluginManager $m)
    {
        try {
            $result = $m->callAction($slug, $action);
        } catch (UnknownPluginException|PluginException) {
            abort(404);
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }

        $this->audit('run_plugin_action', null, request());

        if (is_string($result) && $result !== '') {
            return back()->with('ok', $result);
        }

        return back()->with('ok', "Ran '{$action}' on {$slug}");
    }

    /** Past-tense label for a lifecycle verb. */
    protected function verb(string $action): string
    {
        return [
            'install' => 'Installed: ',
            'activate' => 'Activated: ',
            'deactivate' => 'Deactivated: ',
            'uninstall' => 'Uninstalled: ',
            'update' => 'Updated: ',
        ][$action] ?? 'Ran: ';
    }

    public function settings()
    {
        return view('admin.plugins.settings', [
            'plugins' => Plugin::orderBy('name')->get(),
            'values' => app(\App\Core\Services\SettingService::class)->all(),
        ]);
    }

    public function saveSettings(Request $r)
    {
        $posted = (array) $r->input('settings', []);
        if ($posted === []) {
            return back()->withErrors(['msg' => 'Nothing submitted.']);
        }

        $svc = app(\App\Core\Services\SettingService::class);
        foreach ($posted as $key => $value) {
            $group = \Illuminate\Support\Str::before($key, '.') ?: 'plugins';
            $type = is_array($value) ? 'json' : ($r->boolean("settings.{$key}.__bool") ? 'boolean' : 'text');
            $svc->set($key, is_array($value) ? json_encode($value) : $value, $type, $group);
        }

        $this->audit('update_plugin_settings', null, $r);

        return back()->with('ok', 'Plugin settings saved');
    }
}
