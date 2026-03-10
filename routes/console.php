<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sticky note reminders: run every minute so reminders are sent when reminder_datetime is reached
// Schedule::command('sticky-note:send-reminder')->everyMinute();

// Process queued jobs (e.g. Rescheduled & Cancelled flight check). Runs in background so cron returns quickly.
// withoutOverlapping(6) avoids starting a new run if one started in the last 6 minutes (job can take a few minutes).
// Use with a single cPanel cron: * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
Schedule::command('queue:work --stop-when-empty --max-time=300')->everyMinute()->withoutOverlapping(6)->runInBackground();
