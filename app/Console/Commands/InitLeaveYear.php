<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\LeaveService;
use Illuminate\Console\Command;

class InitLeaveYear extends Command
{
    protected $signature = 'leave:init-year {year? : The year to initialise (defaults to current year)}';

    protected $description = 'Create leave balances for all active employees for the given year';

    public function handle(LeaveService $leaveService): int
    {
        $year = (int) ($this->argument('year') ?? now()->year);

        $this->info("Initialising leave balances for {$year}...");

        $employees = User::where('is_active', true)->get();

        $created = 0;
        $skipped = 0;

        foreach ($employees as $employee) {
            $existing = \App\Models\LeaveBalance::where('user_id', $employee->id)
                ->where('year', $year)
                ->exists();

            if ($existing) {
                $skipped++;
                continue;
            }

            $leaveService->getOrCreateBalance($employee, $year);
            $created++;
            $this->line("  ✓ {$employee->name}");
        }

        $this->newLine();
        $this->info(__('leave.init_year_done', ['count' => $created]));

        if ($skipped > 0) {
            $this->warn(__('leave.init_year_skipped', ['count' => $skipped]));
        }

        return self::SUCCESS;
    }
}
