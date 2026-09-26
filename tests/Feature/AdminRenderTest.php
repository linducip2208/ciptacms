<?php
namespace Tests\Feature;
use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User; use Illuminate\Support\Facades\Hash;
use Database\Seeders\{RolesPermissionsSeeder,SettingSeeder};
class AdminRenderTest extends TestCase {
    use RefreshDatabase;
    public function test_all_admin_pages_render(): void {
        $this->seed([RolesPermissionsSeeder::class, SettingSeeder::class]);
        $u=User::create(['name'=>'A','email'=>'a@a.local','password'=>Hash::make('password123'),'status'=>'active','is_active'=>true]);
        $urls=['/admin','/admin/users','/admin/roles','/admin/menus','/admin/modules','/admin/plugins','/admin/themes','/admin/media','/admin/settings','/admin/cms/pages','/admin/cms/posts','/admin/cms/comments','/admin/cms/forms','/admin/cms/content-types','/admin/cms/workflows','/admin/cms/webhooks','/admin/cms/seo','/admin/tenants','/admin/plans','/admin/licenses','/admin/gateways','/admin/health','/admin/info','/admin/audits','/admin/backups','/admin/updates','/admin/search?q=x','/admin/security/2fa','/admin/security/sessions','/admin/api-docs','/admin/queue','/admin/r/products','/admin/r/orders','/admin/r/leads'];
        foreach($urls as $url){ $this->actingAs($u)->get($url)->assertOk("GET {$url}"); }
    }
}
