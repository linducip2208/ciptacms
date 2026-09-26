<?php

namespace App\Console\Commands;

use App\Core\Services\InstallLock;
use Illuminate\Console\Command;

/**
 * Reopen the browser installer.
 *
 * This is the deliberate recovery mechanism: the web installer is locked once
 * an installation completes, and only a shell on the server can lift that.
 * A --force confirmation is required so it cannot be undone by a stray call.
 */
class LinduUnlockCommand extends Command
{
    protected $signature = 'lindu:unlock
                            {--force : Confirm removing the install lock}
                            {--token : Print the recovery token instead of unlocking}';

    protected $description = 'Remove the Lindu CMS install lock so /install can run again';

    public function handle(InstallLock $lock): int
    {
        if ($lock->installed()) {
            $info = $lock->payload() ?? [];
            $this->line('Installed: '.($info['installed_at'] ?? 'unknown'));
            $this->line('Version:   '.($info['version'] ?? 'unknown'));
        }

        if ($this->option('token')) {
            $this->line($lock->recoveryToken());

            return self::SUCCESS;
        }

        if (! $lock->installed()) {
            $this->info('No install lock present. /install is already open.');
            $this->line('Recovery token: '.$lock->recoveryToken());

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            $this->error('Refusing to unlock without --force.');
            $this->line('Re-run with: php artisan lindu:unlock --force');

            return self::FAILURE;
        }

        if ($lock->unlock()) {
            $this->info('Install lock removed. /install is open again.');
            $this->comment('Re-lock after reinstalling with: php artisan lindu:unlock again is not needed — /install re-locks on success.');

            return self::SUCCESS;
        }

        $this->error('Could not remove the lock file at '.$lock->path());

        return self::FAILURE;
    }
}
