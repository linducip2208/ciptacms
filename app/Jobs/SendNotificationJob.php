<?php
namespace App\Jobs;
use Illuminate\Bus\Queueable; use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable; use Illuminate\Queue\InteractsWithQueue; use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
class SendNotificationJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public string $to, public string $subject, public string $body) {}
    public function handle(): void { Mail::raw($this->body, fn($m)=>$m->to($this->to)->subject($this->subject)); }
}
