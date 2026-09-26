<?php
namespace App\Http\Controllers\Api\V1;
use Illuminate\Http\Request; use App\Models\Webhook;
class WebhookApiController extends ApiController {
    public function incoming(Request $r, string $key){
        $w=Webhook::where('secret',$key)->where('is_active',true)->first();
        if(!$w) return $this->error('Unknown webhook',404);
        try{ app(\App\Core\Services\WorkflowEngine::class)->trigger('webhook.received',array_merge($r->all(),['_webhook'=>$w->id])); }catch(\Throwable $e){}
        return $this->data(['ok'=>true]);
    }
}
