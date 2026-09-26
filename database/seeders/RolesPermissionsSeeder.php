<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\{Role,Permission,PermissionGroup};
class RolesPermissionsSeeder extends Seeder {
    public function run(): void {
        $groups = ['cms'=>'CMS','commerce'=>'Commerce','apps'=>'Applications','system'=>'System','saas'=>'SaaS'];
        foreach($groups as $slug=>$name) PermissionGroup::firstOrCreate(['slug'=>$slug],['name'=>$name]);
        $defs = [
            ['cms','pages','Pages',['view','create','read','update','delete','publish','export','import']],
            ['cms','posts','Posts',['view','create','read','update','delete','publish','export']],
            ['cms','media','Media',['view','create','read','update','delete','manage']],
            ['cms','menus','Menus',['view','create','read','update','delete','manage']],
            ['cms','forms','Forms',['view','create','read','update','delete','manage','export']],
            ['cms','seo','SEO',['view','update','manage']],
            ['commerce','products','Products',['view','create','read','update','delete','export','import']],
            ['commerce','orders','Orders',['view','create','read','update','delete','approve','export']],
            ['commerce','customers','Customers',['view','create','read','update','delete','export']],
            ['apps','pos','POS',['view','create','read','update','delete','manage']],
            ['apps','hotel','Hotel',['view','create','read','update','delete','manage']],
            ['apps','lms','LMS',['view','create','read','update','delete','manage']],
            ['apps','crm','CRM',['view','create','read','update','delete','manage','export']],
            ['apps','marketplace','Marketplace',['view','create','read','update','delete','approve','manage']],
            ['apps','jodohku','Jodohku',['view','create','read','update','delete','approve','manage']],
            ['system','users','Users',['view','create','read','update','delete','manage']],
            ['system','roles','Roles',['view','create','read','update','delete','manage','configure']],
            ['system','settings','Settings',['view','update','configure','manage']],
            ['system','modules','Modules',['view','manage','configure']],
            ['saas','tenants','Tenants',['view','create','read','update','delete','manage','configure']],
            ['saas','billing','Billing',['view','manage','configure','export']],
        ];
        foreach($defs as [$g,$mod,$label,$actions]){
            $grp = PermissionGroup::where('slug',$g)->first();
            foreach($actions as $a){ Permission::firstOrCreate(['slug'=>"{$mod}.{$a}"],['group_id'=>$grp?->id,'name'=>"{$label} ".ucfirst($a),'action'=>$a,'module'=>$mod]); }
        }
        foreach([['Super Admin','super-admin',100,1],['Admin','admin',90,1],['Manager','manager',50,0],['Member','member',10,0]] as [$n,$s,$lvl,$sys]){
            $r = Role::firstOrCreate(['slug'=>$s],['name'=>$n,'level'=>$lvl,'is_system'=>(bool)$sys]);
            if(in_array($s,['super-admin','admin'])) $r->permissions()->sync(\App\Models\Permission::pluck('id'));
        }
    }
}
