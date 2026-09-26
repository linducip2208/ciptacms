<?php
namespace App\Jobs;
use Illuminate\Bus\Queueable; use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable; use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\MediaFile;
class ProcessMediaJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 3;
    public function __construct(public int $mediaId) {}
    public function handle(): void {
        $f = MediaFile::find($this->mediaId); if(!$f) return;
        app(\App\Core\Services\MediaService::class)->process($f);
    }
}
