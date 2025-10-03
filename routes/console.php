<?php

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Notifications pruning schedule
// Daily prune: remove read notifications older than 3 days
Schedule::command('notifications:prune --days=3')->dailyAt('02:30')->withoutOverlapping();

// Weekly deep prune: remove all notifications (including unread) older than 60 days
Schedule::command('notifications:prune --days=60 --all')->weeklyOn(1, '03:00')->withoutOverlapping();

// Activity log cleanup: keep only last 7 days
Schedule::command('activitylog:clean')->weeklyOn(1, '03:15')->withoutOverlapping();
