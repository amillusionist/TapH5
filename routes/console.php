<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Background Tasks
|--------------------------------------------------------------------------
|
| Automated game discovery scanner runs periodically under Laravel Scheduler.
| Uses cache lock to prevent overlapping runs, fully compatible with Hostinger
| standard cron execution.
|
*/
Schedule::command('games:scan')
    ->everySixHours()
    ->withoutOverlapping(60)
    ->name('taph5-game-scanner');
