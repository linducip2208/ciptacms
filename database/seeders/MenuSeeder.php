<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder; use App\Models\MenuItem;
class MenuSeeder extends Seeder {
    public function run(): void {
        MenuItem::query()->delete();
        $items = [
            ['dashboard','Dashboard','🏠',null,'/admin',null,0],
            ['pages','Pages','📄','pages.view','/admin/cms/pages',null,10],
            ['posts','Posts','✏️','posts.view','/admin/cms/posts',null,20],
            ['media','Media','🖼️','media.view','/admin/media',null,30],
            ['products','Products','📦','products.view','/admin/r/products',null,40],
            ['orders','Orders','🧾','orders.view','/admin/r/orders',null,50],
            ['leads','CRM','🎯','crm.view','/admin/r/leads',null,60],
            ['users','Users','👤','users.view','/admin/users',null,70],
            ['roles','Roles','🛡️','roles.view','/admin/roles',null,80],
            ['menus','Menus','🧭','menus.view','/admin/menus',null,90],
            ['modules','Modules','📦','modules.view','/admin/modules',null,100],
            ['settings','Settings','⚙️','settings.view','/admin/settings',null,110],
        ];
        foreach($items as [$slug,$title,$icon,$perm,$url,$parent,$sort]){
            MenuItem::firstOrCreate(['location'=>'admin','title'=>$title],['icon'=>$icon,'url'=>$url,'permission'=>$perm,'sort_order'=>$sort,'is_visible'=>true,'module'=>'core']);
        }
    }
}
