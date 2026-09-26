<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder; use Illuminate\Support\Str; use App\Models\{Tenant,Language,Theme,Module,Plugin};
class TenantLicenseSeeder extends Seeder {
    public function run(): void {
        Language::firstOrCreate(['code'=>'id'],['name'=>'Indonesian','is_default'=>true,'is_active'=>true]);
        Language::firstOrCreate(['code'=>'en'],['name'=>'English','is_active'=>true]);
        Theme::firstOrCreate(['slug'=>'default'],['name'=>'Lindu Default','version'=>'1.0.0','is_active'=>true,'description'=>'Responsive default theme']);
        Theme::firstOrCreate(['slug'=>'dark-commerce'],['name'=>'Dark Commerce','version'=>'1.0.0','description'=>'Dark storefront']);
        $id=(string)Str::uuid();
        Tenant::firstOrCreate(['slug'=>'demo'],['id'=>$id,'uuid'=>$id,'name'=>'Demo Tenant','subdomain'=>'demo','status'=>'active']);
        \App\Models\License::firstOrCreate(['license_key'=>'LND-DEMO-0001'],['product'=>'Lindu CMS','customer'=>'Demo','status'=>'active','features'=>['all'],'max_activations'=>5]);
    }
}
