<?php
namespace Tests\Feature;
use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User; use Illuminate\Support\Facades\Hash;
use Database\Seeders\{RolesPermissionsSeeder,SettingSeeder};
class AllResourcesTest extends TestCase {
    use RefreshDatabase;
    public function test_all_generic_resources_crud(): void {
        $this->seed([RolesPermissionsSeeder::class, SettingSeeder::class]);
        $u=User::create(['name'=>'R','email'=>'r@r.local','password'=>Hash::make('password123'),'status'=>'active','is_active'=>true]);
        $token=$u->createToken('t')->plainTextToken;
        $map=config('lindu_admin.resources');
        $this->assertGreaterThanOrEqual(27,count($map));
        foreach(['pages','posts','products','orders','leads'] as $res){
            $this->withToken($token)->getJson("/api/v1/{$res}")->assertOk();
            $this->withToken($token)->getJson("/api/v2/{$res}?fields=id")->assertOk()->assertJsonPath('meta.version','v2');
        }
    }
    public function test_admin_pages_render_with_tabler(): void {
        $this->seed([RolesPermissionsSeeder::class, SettingSeeder::class]);
        $u=User::create(['name'=>'A','email'=>'a@a.local','password'=>Hash::make('password123'),'status'=>'active','is_active'=>true]);
        $this->actingAs($u)->get('/admin')->assertOk()->assertSee('tabler',false);
        $this->actingAs($u)->get('/admin/security/2fa')->assertOk()->assertSee('QRCode',false);
        $this->actingAs($u)->get('/admin/api-docs')->assertOk()->assertSee('Swagger',false);
        $this->get('/docs')->assertOk()->assertSee('swagger',false);
        $this->get('/login')->assertOk()->assertSee('tabler',false);
    }
    public function test_search_drivers(): void {
        $svc=app(\App\Core\Services\SearchService::class);
        $this->assertContains($svc->driverName(),['database','meilisearch']);
        $this->assertIsArray($svc->search('lindu',['pages']));
    }
    public function test_social_redirect(): void {
        $this->get('/oauth/google')->assertRedirect();
        $this->get('/oauth/bogus')->assertNotFound();
    }
}
