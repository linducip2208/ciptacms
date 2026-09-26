<?php
namespace Tests\Feature;
use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Workflow;
class WorkflowTest extends TestCase {
    use RefreshDatabase;
    public function test_trigger_runs(): void {
        $f = Workflow::create(['name'=>'T','trigger_event'=>'record.created','conditions'=>[],'actions'=>[],'is_active'=>true]);
        app(\App\Core\Services\WorkflowEngine::class)->trigger('record.created',['x'=>1]);
        $this->assertDatabaseHas('workflow_runs',['workflow_id'=>$f->id,'status'=>'completed']);
    }
}
