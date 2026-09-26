<?php
namespace Tests\Feature;
use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Module;
class ModuleLifecycleTest extends TestCase {
    use RefreshDatabase;
    public function test_lifecycle(): void {
        $m = Module::create(['slug'=>'jodohku','name'=>'Jodohku','is_installed'=>true,'is_active'=>false]);
        $mgr = app(\App\Core\Services\ModuleManager::class);
        $mgr->activate('jodohku'); $this->assertTrue($mgr->isActive('jodohku'));
        $mgr->deactivate('jodohku'); $this->assertFalse(Module::where('slug','jodohku')->first()->is_active);
    }
}
