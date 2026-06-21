<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily housekeeping: flip expired subscriptions from active → expired so the
// status column matches reality. Wallets are deliberately left alone — points
// earned before / during a subscription stay in the student's wallet.
Schedule::command('subscriptions:expire')->dailyAt('00:05');

// Weekly parent digest: every Saturday 08:00 (start of the study week).
Schedule::command('digest:parent-weekly')->weeklyOn(6, '08:00');

// Daily lesson reminders: today's new lessons + overdue un-opened ones, 07:00.
Schedule::command('reminders:lessons')->dailyAt('07:00');
