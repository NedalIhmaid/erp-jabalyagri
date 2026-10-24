<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Initialise leave balances for all active employees on 1 Jan each year at 06:00
Schedule::command('leave:init-year')->yearlyOn(1, 1, '06:00');

// WhatsApp attendance reminders on work days, in the attendance timezone
foreach (['check_in', 'check_out'] as $attendanceType) {
    $reminder = Schedule::command("attendance:remind {$attendanceType}")
        ->dailyAt(config("services.whatsapp.attendance.{$attendanceType}_reminder_at"))
        ->timezone(config('services.whatsapp.attendance.timezone'))
        ->when(fn (): bool => (bool) config('services.whatsapp.attendance.enabled'));

    if (config('services.whatsapp.attendance.work_days') !== []) {
        $reminder->days(config('services.whatsapp.attendance.work_days'));
    }
}
