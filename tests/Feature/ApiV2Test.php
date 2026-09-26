<?php
namespace Tests\Feature;
use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User; use Illuminate\Support\Facades\Hash;
use Database\Seeders\{RolesPermissionsSeeder,SettingSeeder};
class ApiV2Test extends TestCase {
    use RefreshDatabase;
    public function test_v2_sparse_and_include(): void {
        $this->seed([RolesPermissionsSeeder::class, SettingSeeder::class]);
        $u=User::create(['name'=>'A','email'=>'a@a.local','password'=>Hash::make('password123'),'status'=>'active','is_active'=>true]);
        $t=$u->createToken('t')->plainTextToken;
        $this->withToken($t)->getJson('/api/v2/pages?fields=id,title')->assertOk()->assertJsonPath('meta.version','v2');
        $this->withToken($t)->getJson('/api/v2/orders?include=items')->assertOk();
        $this->get('/api/docs/openapi.json')->assertOk();
    }
    public function test_page_builder_save(): void {
        $this->seed([RolesPermissionsSeeder::class, SettingSeeder::class]);
        $u=User::create(['name'=>'B','email'=>'b@b.local','password'=>Hash::make('password123'),'status'=>'active','is_active'=>true]);
        $this->actingAs($u)->post('/admin/cms/pages',['title'=>'Builder T','slug'=>'builder-t','builder'=>json_encode(['sections'=>[['name'=>'S','blocks'=>[['type'=>'heading','heading'=>'Hi']]]]])])->assertRedirect();
        $this->assertDatabaseHas('pages',['title'=>'Builder T']);
    }
}
