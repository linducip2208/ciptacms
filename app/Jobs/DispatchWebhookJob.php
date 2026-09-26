<?php
namespace App\Jobs;
use Illuminate\Bus\Queueable; use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable; use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels; use Illuminate\Support\Facades\Http;
use App\Models\Webhook; use App\Models\WebhookLog;
class DispatchWebhookJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 5;
    public function __construct(public string $webhookId, public array $payload) {}
    public function handle(): void {
        $w = Webhook::find($this->webhookId); if(!$w||!$w->is_active) return;
        $log = WebhookLog::where('webhook_id',$w->id)->where('status','pending')->latest()->first();
        try {
            $body = json_encode($this->payload);
            $sig = hash_hmac('sha256', $body, $w->secret ?? '');
            $res = Http::timeout($w->timeout??10)->withHeaders(array_merge($w->headers??[],['X-Webhook-Signature'=>$sig,'X-Webhook-Event'=>$w->event]))->withBody($body,'application/json')->post($w->url);
            $log?->update(['status'=>$res->successful()?'delivered':'failed','attempts'=>($log->attempts??0)+1,'response'=>substr((string)$res->body(),0,2000),'next_retry_at'=>$res->successful()?null:now()->addMinutes(5*($log->attempts??1))]);
            if(!$res->successful()) throw new \RuntimeException('Webhook failed '.$res->status());
        } catch(\Throwable $e){
            $log?->update(['status'=>'failed','attempts'=>($log->attempts??0)+1,'response'=>substr($e->getMessage(),0,2000),'next_retry_at'=>now()->addMinutes(10)]);
            throw $e;
        }
    }
}
