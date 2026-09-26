<?php
namespace App\Core\Services;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
class WorkflowEngine {
    public function trigger(string $event, array $payload=[]): void {
        try {
            $flows = Workflow::where('is_active',true)->where('trigger_event',$event)->get();
            foreach($flows as $f) $this->run($f,$payload);
        } catch(\Throwable $e){}
    }
    public function run(Workflow $f, array $payload=[]): WorkflowRun {
        $run = WorkflowRun::create(['workflow_id'=>$f->id,'status'=>'running','payload'=>$payload,'started_at'=>now()]);
        try {
            if(!$this->conditionsPass($f->conditions??[],$payload)) { $run->update(['status'=>'skipped','finished_at'=>now()]); return $run->fresh(); }
            foreach((array)($f->actions??[]) as $a) $this->doAction($a,$payload);
            $run->update(['status'=>'completed','finished_at'=>now()]);
        } catch(\Throwable $e){ $run->update(['status'=>'failed','finished_at'=>now(),'log'=>$e->getMessage()]); }
        return $run->fresh();
    }
    protected function conditionsPass(array $conds, array $payload): bool {
        foreach($conds as $c){
            $field=$c['field']??''; $op=$c['operator']??'equals'; $val=$c['value']??null;
            $actual=data_get($payload,$field);
            $ok = match($op){
                'equals'=>$actual==$val,'not_equals'=>$actual!=$val,
                'greater_than'=>$actual>$val,'less_than'=>$actual<$val,
                'contains'=>str_contains((string)$actual,(string)$val),
                'in_list'=>in_array($actual,(array)$val),
                'boolean'=>((bool)$actual)===filter_var($val,FILTER_VALIDATE_BOOLEAN),
                default=>true,
            };
            if(!$ok) return false;
        }
        return true;
    }
    protected function doAction(array $a, array $payload): void {
        $type=$a['type']??'';
        match($type){
            'send_email'=> $this->sendEmail($a,$payload),
            'send_webhook'=> $this->sendWebhook($a,$payload),
            'send_http'=> $this->sendWebhook($a,$payload),
            'create_record'=> $this->createRecord($a,$payload),
            'update_record'=> $this->updateRecord($a,$payload),
            default=> null,
        };
        try { app(WebhookDispatcher::class)->dispatchEvent($a['event']??'workflow.action', array_merge($payload,['action'=>$type])); } catch(\Throwable $e){}
    }
    protected function sendEmail($a,$p){ if(!empty($a['to'])) Mail::raw($a['body']??'Workflow notification', fn($m)=>$m->to($a['to'])->subject($a['subject']??'Notification')); }
    protected function sendWebhook($a,$p){ if(!empty($a['url'])) Http::timeout(10)->post($a['url'], $p); }
    protected function createRecord($a,$p){ $model=$a['model']??null; if($model&&class_exists($model)) $model::create($a['data']??[]); }
    protected function updateRecord($a,$p){ $model=$a['model']??null; if($model&&class_exists($model)&&!empty($a['id'])) $model::where('id',$a['id'])->update($a['data']??[]); }
}
