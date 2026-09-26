<?php
namespace Tests\Feature;
use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User; use Illuminate\Support\Facades\Hash;
use Database\Seeders\{RolesPermissionsSeeder,SettingSeeder};
class E2eBuilderFlowTest extends TestCase {
    use RefreshDatabase;
    public function test_full_builder_to_api_render_flow(): void {
        $this->seed([RolesPermissionsSeeder::class, SettingSeeder::class]);
        $u=User::create(['name'=>'E','email'=>'e@e.local','password'=>Hash::make('password123'),'status'=>'active','is_active'=>true]);
        $builder=['sections'=>[['name'=>'Hero','layout'=>'hero','visibility'=>['desktop'=>'show','tablet'=>'show','mobile'=>'show'],'blocks'=>[['type'=>'heading','heading'=>'Halo'],['type'=>'button','heading'=>'Mulai','link'=>'/blog']]]]];
        $this->actingAs($u)->post('/admin/cms/pages',['title'=>'E2E Page','slug'=>'e2e-page','status'=>'published','builder'=>json_encode($builder)])->assertRedirect();
        $this->assertDatabaseHas('pages',['slug'=>'e2e-page']);
        $html=\App\Core\Services\BlockLibrary::render($builder);
        $this->assertStringContainsString('Halo',$html);
        $token=$u->createToken('t')->plainTextToken;
        $this->withToken($token)->getJson('/api/v2/pages?search=e2e')->assertOk();
        $this->actingAs($u)->get('/admin/queue')->assertOk();
        $this->actingAs($u)->post('/admin/uploads/presign',['name'=>'x.jpg','mime'=>'image/jpeg'])->assertOk()->assertJsonPath('ok',true);
        $this->get('/docs')->assertOk();
    }
}
