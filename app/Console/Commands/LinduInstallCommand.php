<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Artisan,Hash};
class LinduInstallCommand extends Command {
    protected $signature='lindu:install {--admin-email=admin@lindu.local} {--admin-password=password123} {--fresh}';
    protected $description='Install Lindu CMS seed data';
    public function handle(){
        if($this->option('fresh')){ $this->warn('Running migrate:fresh'); Artisan::call('migrate:fresh',['--force'=>true]); }
        else Artisan::call('migrate',['--force'=>true]);
        Artisan::call('db:seed',['--force'=>true]);
        $this->info('Lindu CMS installed.');
    }
}
