<?php

namespace App\Http\Controllers\Admin;

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

    public function action(string $slug, string $action, PluginManager $m)
    {
        abort_unless(in_array($action, ['install', 'activate', 'deactivate', 'uninstall', 'update'], true), 404);

        try {
            $m->$action($slug);

            return back()->with('ok', ucfirst($action).'d: '.$slug);
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
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
