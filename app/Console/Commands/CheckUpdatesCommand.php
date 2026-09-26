<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class CheckUpdatesCommand extends Command {
    protected $signature='lindu:check-updates'; protected $description='Check core/module updates';
    public function handle(){ $r=app(\App\Core\Services\UpdateService::class)->check('core'); $this->info(json_encode($r)); }
}
