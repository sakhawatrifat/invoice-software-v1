<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sticky note reminders: run every minute so reminders are sent when reminder_datetime is reached
Schedule::command('sticky-note:send-reminder')->everyMinute();
