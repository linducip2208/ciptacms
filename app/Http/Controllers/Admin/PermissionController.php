<?php

namespace App\Http\Controllers\Admin;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use Illuminate\Http\Request;

class PermissionController extends AdminController
{
    public function index(Request $r)
    {
        $q = Permission::with('group')->orderBy('group_id')->orderBy('slug');
        if ($search = $r->get('search')) {
            $q->where(function ($w) use ($search) {
                $w->where('slug', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%");
            });
        }
        if ($module = $r->get('module')) {
            $q->where('module', $module);
        }

        return view('admin.permissions.index', [
            'rows' => $q->paginate(50)->withQueryString(),
            'modules' => Permission::whereNotNull('module')->distinct()->orderBy('module')->pluck('module'),
            'groups' => PermissionGroup::orderBy('name')->get(),
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|max:190',
            'slug' => 'required|string|max:190|unique:permissions,slug',
            'action' => 'nullable|in:view,create,read,update,delete,publish,approve,export,import,manage,configure',
            'module' => 'nullable|string|max:100',
            'group_id' => 'nullable|exists:permission_groups,id',
            'description' => 'nullable|string',
        ]);

        $data['slug'] = \Illuminate\Support\Str::slug($data['slug'], '.');
        $data['action'] = $data['action'] ?? $this->inferAction($data['slug']);

        $p = Permission::create($data);
        $this->audit('create_permission', $p, $r);

        return back()->with('ok', 'Permission created');
    }

    public function update(Request $r, Permission $permission)
    {
        $data = $r->validate([
            'name' => 'required|string|max:190',
            'slug' => 'required|string|max:190|unique:permissions,slug,'.$permission->id,
            'action' => 'nullable|in:view,create,read,update,delete,publish,approve,export,import,manage,configure',
            'module' => 'nullable|string|max:100',
            'group_id' => 'nullable|exists:permission_groups,id',
            'description' => 'nullable|string',
        ]);

        $data['slug'] = \Illuminate\Support\Str::slug($data['slug'], '.');
        $permission->update($data);
        $this->audit('update_permission', $permission, $r);

        return back()->with('ok', 'Permission updated');
    }

    public function destroy(Request $r, Permission $permission)
    {
        $inUse = $permission->roles()->count();
        if ($inUse > 0) {
            return back()->withErrors(['msg' => "This permission is assigned to {$inUse} role(s). Detach it first."]);
        }

        $permission->delete();
        $this->audit('delete_permission', $permission, $r);

        return back()->with('ok', 'Permission deleted');
    }

    public function groups()
    {
        return view('admin.permissions.groups', [
            'rows' => PermissionGroup::withCount('permissions')->orderBy('name')->get(),
        ]);
    }

    public function storeGroup(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|max:190',
            'slug' => 'required|string|max:190|unique:permission_groups,slug',
            'description' => 'nullable|string',
        ]);

        $g = PermissionGroup::create($data + ['slug' => \Illuminate\Support\Str::slug($data['slug'], '-')]);
        $this->audit('create_permission_group', $g, $r);

        return back()->with('ok', 'Group created');
    }

    public function updateGroup(Request $r, PermissionGroup $group)
    {
        $data = $r->validate([
            'name' => 'required|string|max:190',
            'slug' => 'required|string|max:190|unique:permission_groups,slug,'.$group->id,
            'description' => 'nullable|string',
        ]);

        $group->update($data + ['slug' => \Illuminate\Support\Str::slug($data['slug'], '-')]);
        $this->audit('update_permission_group', $group, $r);

        return back()->with('ok', 'Group updated');
    }

    public function destroyGroup(Request $r, PermissionGroup $group)
    {
        if ($group->permissions()->exists()) {
            return back()->withErrors(['msg' => 'Move or delete the permissions in this group first.']);
        }

        $group->delete();
        $this->audit('delete_permission_group', $group, $r);

        return back()->with('ok', 'Group deleted');
    }

    protected function inferAction(string $slug): string
    {
        $tail = \Illuminate\Support\Str::afterLast($slug, '.');

        return in_array($tail, ['view', 'create', 'read', 'update', 'delete', 'publish', 'approve', 'export', 'import', 'manage', 'configure'], true)
            ? $tail
            : 'view';
    }
}
