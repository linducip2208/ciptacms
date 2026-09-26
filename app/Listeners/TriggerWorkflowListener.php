<?php
namespace App\Listeners;
use App\Core\Services\WorkflowEngine;
class TriggerWorkflowListener {
    public function handle($event): void {
        try {
            $name = class_basename($event);
            $map = ['RecordCreated'=>'record.created','FormSubmitted'=>'form.submitted','Registered'=>'user.registered'];
            $ev = $map[$name] ?? strtolower($name);
            app(WorkflowEngine::class)->trigger($ev, (array)$event);
        } catch(\Throwable $e){}
    }
}
