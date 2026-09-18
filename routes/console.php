<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Initialise leave balances for all active employees on 1 Jan each year at 06:00
Schedule::command('leave:init-year')->yearlyOn(1, 1, '06:00');
