<?php
namespace App\Core\Services;
use App\Models\Webhook;
use App\Models\WebhookLog;
use App\Jobs\DispatchWebhookJob;
class WebhookDispatcher {
    public function dispatchEvent(string $event, array $payload=[]): void {
        try {
            foreach(Webhook::where('is_active',true)->where('event',$event)->get() as $w){
                WebhookLog::create(['webhook_id'=>$w->id,'event'=>$event,'payload'=>$payload,'status'=>'pending']);
                DispatchWebhookJob::dispatch($w->id, $payload);
            }
        } catch(\Throwable $e){}
    }
}
