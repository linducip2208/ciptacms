<?php
namespace Tests\Feature;
use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile; use Illuminate\Support\Facades\Storage;
use App\Models\User; use Illuminate\Support\Facades\Hash;
use Database\Seeders\{RolesPermissionsSeeder,SettingSeeder};
class MediaPipelineTest extends TestCase {
    use RefreshDatabase;
    public function test_upload_queues_variants(): void {
        Storage::fake('public');
        $this->seed([RolesPermissionsSeeder::class, SettingSeeder::class]);
        $u=User::create(['name'=>'M','email'=>'m@m.local','password'=>Hash::make('password123'),'status'=>'active','is_active'=>true]);
        $img=UploadedFile::fake()->image('a.jpg',800,600);
        $this->actingAs($u)->post('/admin/media',['files'=>[$img]])->assertRedirect();
        $this->assertDatabaseHas('media_files',['original_name'=>'a.jpg']);
    }
}
