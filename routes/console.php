<?php
use Illuminate\Support\Facades\Schedule;
Schedule::command('lindu:backup --type=full')->daily();
Schedule::command('lindu:check-updates')->daily();
Schedule::command('webhooks:retry')->everyFiveMinutes();
Schedule::command('queue:prune-failed --hours=72')->daily();
// Keep plan usage counters honest after manual DB edits or a restored backup.
Schedule::command('lindu:quota-recount')->daily();
