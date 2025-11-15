<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-complete pending donations every minute (DEV ONLY)
Schedule::command('donations:auto-complete')
    ->everyMinute()
    ->onSuccess(function () {
        info('Auto-complete donations task finished');
    })
    ->onFailure(function () {
        info('Auto-complete donations task failed');
    });
