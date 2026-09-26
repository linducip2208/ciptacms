<?php
namespace Tests\Feature;
use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User,Role,Permission}; use Illuminate\Support\Facades\Hash;
use Database\Seeders\RolesPermissionsSeeder;
class RbacTest extends TestCase {
    use RefreshDatabase;
    public function test_permission_flow(): void {
        $this->seed(RolesPermissionsSeeder::class);
        $u = User::create(['name'=>'U','email'=>'u@u.local','password'=>Hash::make('x12345678'),'status'=>'active','is_active'=>true]);
        $this->assertFalse($u->hasPermission('pages.view'));
        $role = \App\Models\Role::where('slug','admin')->first();
        $u->roles()->attach($role);
        $this->assertTrue($u->fresh()->hasPermission('pages.view'));
    }
}
