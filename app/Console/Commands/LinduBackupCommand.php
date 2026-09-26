<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class LinduBackupCommand extends Command {
    protected $signature='lindu:backup {--type=full}';
    protected $description='Run Lindu backup';
    public function handle(){ $b=app(\App\Core\Services\BackupService::class)->run($this->option('type')); $this->info('Backup '.$b->status.' '.$b->path); }
}
