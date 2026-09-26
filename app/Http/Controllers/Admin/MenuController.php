<?php

namespace App\Http\Controllers\Admin;

use App\Core\Services\MenuService;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuController extends AdminController
{
    public const LOCATIONS = ['admin', 'primary', 'footer'];

    public function index(Request $r)
    {
        $location = $r->get('location', 'admin');

        if (! in_array($location, self::LOCATIONS, true)) {
            abort(404, 'Unknown menu location: '.$location);
        }

        $items = MenuItem::where('location', $location)
            ->orderBy('sort_order')
            ->get();

        return view('admin.menus.index', [
            'items' => $items,
            'location' => $location,
            'locations' => self::LOCATIONS,
        ]);
    }

    public function create(Request $r)
    {
        $location = $r->get('location', 'admin');

        return view('admin.menus.form', [
            'item' => new MenuItem(['location' => $location, 'is_visible' => true, 'sort_order' => 0]),
            'locations' => self::LOCATIONS,
            'parents' => $this->parentOptions($location),
        ]);
    }

    public function store(Request $r)
    {
        $data = $this->validated($r);

        $item = MenuItem::create($data);
        MenuService::forget();
        $this->audit('create_menu_item', $item, $r);

        return redirect()
            ->route('admin.menus.index', ['location' => $item->location])
            ->with('ok', 'Menu item "'.$item->title.'" created');
    }

    public function edit(Request $r, MenuItem $menu)
    {
        return view('admin.menus.form', [
            'item' => $menu,
            'locations' => self::LOCATIONS,
            'parents' => $this->parentOptions($menu->location, $menu->id),
        ]);
    }

    public function update(Request $r, MenuItem $menu)
    {
        $data = $this->validated($r, $menu);

        $menu->update($data);
        MenuService::forget();
        $this->audit('update_menu_item', $menu, $r);

        return redirect()
            ->route('admin.menus.index', ['location' => $menu->location])
            ->with('ok', 'Menu item updated');
    }

    public function destroy(Request $r, MenuItem $menu)
    {
        $children = $menu->children()->count();

        if ($children > 0) {
            return back()->withErrors([
                'msg' => "This item has {$children} child item(s). Move or delete them first.",
            ]);
        }

        $menu->delete();
        MenuService::forget();
        $this->audit('delete_menu_item', $menu, $r);

        return back()->with('ok', 'Menu item deleted');
    }

    /**
     * Persist a new sort order. The client sends the ids in display order;
     * parent_id is applied from the request so a drag between groups works.
     */
    public function reorder(Request $r)
    {
        $data = $r->validate([
            'order' => 'required|array',
            'order.*' => 'integer',
            'parent_id' => 'nullable|integer',
        ]);

        foreach ($data['order'] as $position => $id) {
            MenuItem::where('id', $id)->update([
                'sort_order' => $position,
                'parent_id' => $data['parent_id'] ?? null,
            ]);
        }

        MenuService::forget();
        $this->audit('reorder_menu', null, $r);

        return response()->json(['ok' => true, 'count' => count($data['order'])]);
    }

    protected function validated(Request $r, ?MenuItem $menu = null): array
    {
        $d = $r->validate([
            'title' => 'required|string|max:190',
            'location' => 'required|in:'.implode(',', self::LOCATIONS),
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('menu_items', 'id'),
                // A menu item must not become its own ancestor.
                $menu ? Rule::notIn($this->descendantIds($menu)) : '',
            ],
            'url' => 'nullable|string|max:500',
            'route' => 'nullable|string|max:190',
            'icon' => 'nullable|string|max:100',
            'permission' => 'nullable|string|max:190',
            'badge' => 'nullable|string|max:50',
            'badge_color' => 'nullable|string|max:50',
            'target' => 'nullable|in:_self,_blank',
            'sort_order' => 'nullable|integer',
            'meta' => 'nullable',
        ]);

        $d['is_visible'] = $r->boolean('is_visible', true);
        $d['target'] = $d['target'] ?? '_self';

        if (! empty($d['meta']) && is_string($d['meta'])) {
            $decoded = json_decode($d['meta'], true);
            $d['meta'] = is_array($decoded) ? $decoded : null;
        }

        unset($d['meta']);

        return $d;
    }

    /** All descendants of a menu item, so a cycle can be rejected. */
    protected function descendantIds(MenuItem $menu, int $depth = 0): array
    {
        if ($depth > 10) {
            return [$menu->id];
        }

        $ids = [];
        foreach ($menu->children()->pluck('id') as $childId) {
            $ids[] = $childId;
            $child = MenuItem::find($childId);
            if ($child) {
                $ids = array_merge($ids, $this->descendantIds($child, $depth + 1));
            }
        }

        return $ids;
    }

    protected function parentOptions(string $location, ?int $exclude = null)
    {
        return MenuItem::where('location', $location)
            ->when($exclude, fn ($q) => $q->where('id', '!=', $exclude))
            ->orderBy('sort_order')
            ->get(['id', 'title']);
    }
}
