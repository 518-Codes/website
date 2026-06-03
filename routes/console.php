<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('notifications:send-reminders --timing=24h')->everyFifteenMinutes()->withoutOverlapping()->onOneServer();
Schedule::command('notifications:send-reminders --timing=1h')->everyFifteenMinutes()->withoutOverlapping()->onOneServer();
