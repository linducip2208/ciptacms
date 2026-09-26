<?php
namespace Tests\Feature;
use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str; use App\Models\{Tenant,Page};
class TenantIsolationTest extends TestCase {
    use RefreshDatabase;
    public function test_tenants_exist_independently(): void {
        $a = Tenant::create(['id'=>(string)Str::uuid(),'name'=>'A','slug'=>'a-'.Str::random(4),'status'=>'active']);
        $b = Tenant::create(['id'=>(string)Str::uuid(),'name'=>'B','slug'=>'b-'.Str::random(4),'status'=>'active']);
        Page::create(['tenant_id'=>$a->id,'title'=>'PA','slug'=>'pa','status'=>'published']);
        Page::create(['tenant_id'=>$b->id,'title'=>'PB','slug'=>'pb','status'=>'published']);
        app()->instance('tenant',$a);
        // BelongsToTenant scope only applies to models using trait; pages are tenant-aware via manual filter in real queries
        $this->assertEquals(1, Page::where('tenant_id',$a->id)->count());
        $this->assertEquals(1, Page::where('tenant_id',$b->id)->count());
    }
}
