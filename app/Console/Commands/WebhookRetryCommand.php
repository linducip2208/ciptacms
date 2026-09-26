<?php
namespace App\Console\Commands;
use Illuminate\Console\Command; use App\Models\WebhookLog; use App\Jobs\DispatchWebhookJob;
class WebhookRetryCommand extends Command {
    protected $signature='webhooks:retry'; protected $description='Retry failed webhooks';
    public function handle(){ $n=0; foreach(WebhookLog::where('status','failed')->where('next_retry_at','<=',now())->limit(50)->get() as $l){ try{ DispatchWebhookJob::dispatch($l->webhook_id, $l->payload??[]); $n++; }catch(\Throwable $e){} } $this->info("Requeued {$n}"); }
}
