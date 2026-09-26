<?php
namespace Tests\Feature;
use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User; use Illuminate\Support\Facades\Hash;
use Database\Seeders\{RolesPermissionsSeeder,SettingSeeder};
class CmsApiTest extends TestCase {
    use RefreshDatabase;
    public function test_api_auth_and_resources(): void {
        $this->seed([RolesPermissionsSeeder::class, SettingSeeder::class]);
        $u = User::create(['name'=>'Api','email'=>'api@a.local','password'=>Hash::make('password123'),'status'=>'active','is_active'=>true]);
        $token = $u->createToken('t')->plainTextToken;
        $this->withToken($token)->getJson('/api/v1/pages')->assertOk();
        $this->withToken($token)->postJson('/api/v1/pages',['title'=>'Hello','slug'=>'hello-api'])->assertOk();
        $this->assertDatabaseHas('pages',['slug'=>'hello-api']);
    }
}
