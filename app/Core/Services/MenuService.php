<?php
namespace App\Core\Services;
use App\Models\MenuItem;
use Illuminate\Support\Facades\Cache;
class MenuService {
    public function tree(string $location='admin', $user=null): array {
        $key = 'lindu.menu.'.$location.'.'.($user?->id ?? 'guest').'.'.(app()->bound('tenant')&&app('tenant')?app('tenant')->id:'global');
        // SafeCache: the admin sidebar reads this on every page, so a cache
        // entry that fails to decode must degrade, not fatal.
        return \App\Core\Support\SafeCache::remember($key, 120, function () use ($location,$user) {
            $q = MenuItem::where('location',$location)->where('is_visible',true)->orderBy('sort_order');
            if (app()->bound('tenant') && app('tenant')) $q->where(fn($w)=>$w->whereNull('tenant_id')->orWhere('tenant_id', app('tenant')->id));
            $items = $q->get();
            $items = $items->filter(fn($i)=>$this->visible($i,$user));
            return $this->nest($items);
        });
    }
    protected function visible($item, $user): bool {
        if ($item->permission && (!$user || !method_exists($user,'hasPermission') || !$user->hasPermission($item->permission))) return false;
        if ($item->roles) { $roles = is_array($item->roles)?$item->roles:json_decode($item->roles,true); if($roles && (!$user || !$user->roles()->whereIn('slug',$roles)->exists())) return false; }
        return true;
    }
    protected function nest($items, $parent=null): array {
        $out=[];
        foreach ($items->where('parent_id',$parent) as $it) {
            $n=$it->toArray(); $n['children']=$this->nest($items,$it->id);
            $n['active']=request()->is(ltrim((string)($it->url??''),'/').'*') || request()->routeIs($it->route??'__none__');
            $out[]=$n;
        }
        return $out;
    }
    public static function forget(): void { \Illuminate\Support\Facades\Cache::flush(); }

    /**
     * Public URL of a published page by slug, or null when it does not exist.
     *
     * Lets the footer link Privacy and Terms only when an operator has
     * actually published them, instead of 404ing for every install.
     */
    public static function pageUrl(string $slug): ?string
    {
        try {
            $exists = \App\Models\Page::published()->where('slug', $slug)->exists();

            return $exists ? url('/p/'.$slug) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
