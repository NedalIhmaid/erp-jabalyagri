<?php

namespace App\Services;

use App\Enums\HrRequestType;
use App\Models\HrRequest;
use App\Models\LeaveBalance;
use App\Models\User;
use Carbon\Carbon;

class LeaveService
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Years of service completed by the end of the given year (based on hire date).
     */
    public function yearsOfService(User $user, ?int $year = null): int
    {
        if (! $user->hire_date) {
            return 0;
        }

        $year = $year ?? now()->year;

        return (int) Carbon::parse($user->hire_date)
            ->diffInYears(Carbon::createFromDate($year, 12, 31));
    }

    /**
     * Full (non-prorated) annual leave entitlement for the bracket the employee
     * falls in, per Jordan Labor Law:
     *   Years 0-5:  14 days
     *   Years 5-10: 21 days
     *   Years 10+:  30 days
     */
    public function annualBracketDays(User $user, ?int $year = null): int
    {
        if (! $user->hire_date) {
            return 0;
        }

        $yearsWorked = $this->yearsOfService($user, $year);

        if ($yearsWorked >= 10) {
            return 30;
        }

        if ($yearsWorked >= 5) {
            return 21;
        }

        return 14;
    }

    /**
     * Calculate annual leave entitlement based on hire date and Jordan Labor Law.
     * The hire year is pro-rated over the portion of the year actually worked.
     */
    public function calculateAnnualLeaveTotal(User $user, ?int $year = null): float
    {
        if (! $user->hire_date) {
            return 0;
        }

        $year = $year ?? now()->year;
        $hireDate = Carbon::parse($user->hire_date);
        $bracketDays = $this->annualBracketDays($user, $year);

        // First year: pro-rated over remaining days of the hire year
        if ($year === $hireDate->year) {
            $daysInYear = $hireDate->isLeapYear() ? 366 : 365;
            $remainingDays = $hireDate->diffInDays(Carbon::createFromDate($year, 12, 31));

            return round(($bracketDays / $daysInYear) * $remainingDays, 1);
        }

        return $bracketDays;
    }

    /**
     * Monthly-accrued annual leave earned so far for the given year.
     * Each completed month of employment grants (bracket days / 12).
     * Prior years return the full annual total; future years return 0.
     */
    public function accruedAnnual(User $user, ?int $year = null): float
    {
        if (! $user->hire_date) {
            return 0;
        }

        $year = $year ?? now()->year;
        $annualTotal = $this->calculateAnnualLeaveTotal($user, $year);

        // Completed (past) years: fully accrued
        if ($year < now()->year) {
            return $annualTotal;
        }

        // Future years: nothing accrued yet
        if ($year > now()->year) {
            return 0;
        }

        $hireDate = Carbon::parse($user->hire_date);
        $start = $hireDate->greaterThan(Carbon::createFromDate($year, 1, 1))
            ? $hireDate->copy()
            : Carbon::createFromDate($year, 1, 1);
        $end = now()->min(Carbon::createFromDate($year, 12, 31));

        if ($end->lessThan($start)) {
            return 0;
        }

        $completedMonths = min(12, (int) $start->diffInMonths($end));
        $accrued = round(($this->annualBracketDays($user, $year) / 12) * $completedMonths, 1);

        return min($accrued, $annualTotal);
    }

    /**
     * Get or create leave balance for user and year
     */
    public function getOrCreateBalance(User $user, ?int $year = null): LeaveBalance
    {
        $year = $year ?? now()->year;

        $balance = LeaveBalance::firstOrCreate(
            ['user_id' => $user->id, 'year' => $year],
            [
                'annual_total' => $this->calculateAnnualLeaveTotal($user, $year),
                'annual_used' => 0,
                'sick_total' => 14,
                'sick_used' => 0,
                'marriage_used' => false,
                'maternity_used' => 0,
                'bereavement_used' => 0,
            ]
        );

        // Update annual total if hire date changed
        if ($balance->annual_total !== $this->calculateAnnualLeaveTotal($user, $year)) {
            $balance->update([
                'annual_total' => $this->calculateAnnualLeaveTotal($user, $year),
            ]);
        }

        return $balance;
    }

    /**
     * Check if user has enough leave balance
     */
    public function canTakeLeave(User $user, HrRequestType $type, float $days = 0): bool
    {
        $balance = $this->getOrCreateBalance($user);

        return match ($type) {
            HrRequestType::AnnualLeave => $balance->annual_remaining >= $days,
            HrRequestType::SickLeave => $balance->sick_remaining >= $days,
            HrRequestType::MarriageLeave => $balance->marriage_remaining >= $days,
            HrRequestType::MaternityLeave => $balance->maternity_remaining >= $days,
            HrRequestType::BereavementFirst,
            HrRequestType::BereavementSecond => $balance->bereavement_remaining >= $days,
            HrRequestType::UnpaidLeave,
            HrRequestType::EarlyDeparture,
            HrRequestType::DepartureFromAnnual => true, // No balance check needed
        };
    }

    /**
     * Deduct leave when request is approved
     */
    public function deductLeave(User $user, HrRequestType $type, float $days, ?User $causer = null, ?HrRequest $relatedRequest = null): void
    {
        $balance = $this->getOrCreateBalance($user);
        $before = $this->auditLogger->leaveBalanceState($balance);

        $updates = match ($type) {
            HrRequestType::AnnualLeave => ['annual_used' => $balance->annual_used + $days],
            HrRequestType::SickLeave => ['sick_used' => $balance->sick_used + $days],
            HrRequestType::MarriageLeave => ['marriage_used' => true],
            HrRequestType::MaternityLeave => ['maternity_used' => $balance->maternity_used + (int) $days],
            HrRequestType::BereavementFirst,
            HrRequestType::BereavementSecond => ['bereavement_used' => $balance->bereavement_used + 1],
            default => [],
        };

        if (! empty($updates)) {
            $balance->update($updates);
            $this->auditLogger->logLeaveBalanceAdjusted(
                $balance->fresh('user'),
                $causer,
                $before,
                description: 'leave.balance.deducted',
                summary: "Leave balance deducted for {$user->name}",
                context: array_filter([
                    'related_hr_request_id' => $relatedRequest?->id,
                    'related_hr_request_label' => $relatedRequest ? "#{$relatedRequest->id}" : null,
                ]),
            );
        }
    }

    /**
     * Restore leave when an approved request is rejected/cancelled after the fact.
     */
    public function restoreLeave(User $user, HrRequestType $type, float $days, ?User $causer = null, ?HrRequest $relatedRequest = null): void
    {
        $balance = $this->getOrCreateBalance($user);
        $before = $this->auditLogger->leaveBalanceState($balance);

        $updates = match ($type) {
            HrRequestType::AnnualLeave => [
                'annual_used' => max(0, (float) $balance->annual_used - $days),
            ],
            HrRequestType::SickLeave => [
                'sick_used' => max(0, (float) $balance->sick_used - $days),
            ],
            HrRequestType::MarriageLeave => [
                'marriage_used' => false,
            ],
            HrRequestType::MaternityLeave => [
                'maternity_used' => max(0, $balance->maternity_used - (int) $days),
            ],
            HrRequestType::BereavementFirst,
            HrRequestType::BereavementSecond => [
                'bereavement_used' => max(0, $balance->bereavement_used - 1),
            ],
            default => [],
        };

        if (! empty($updates)) {
            $balance->update($updates);
            $this->auditLogger->logLeaveBalanceAdjusted(
                $balance->fresh('user'),
                $causer,
                $before,
                description: 'leave.balance.restored',
                summary: "Leave balance restored for {$user->name}",
                context: array_filter([
                    'related_hr_request_id' => $relatedRequest?->id,
                    'related_hr_request_label' => $relatedRequest ? "#{$relatedRequest->id}" : null,
                ]),
            );
        }
    }

    /**
     * Get all leave balances for user
     */
    public function getUserBalances(User $user): array
    {
        $balance = $this->getOrCreateBalance($user);

        return [
            'annual' => [
                'total' => $balance->annual_total,
                'entitlement' => $this->annualBracketDays($user, $balance->year),
                'accrued' => $this->accruedAnnual($user, $balance->year),
                'used' => $balance->annual_used,
                'remaining' => $balance->annual_remaining,
            ],
            'sick' => [
                'total' => $balance->sick_total,
                'used' => $balance->sick_used,
                'remaining' => $balance->sick_remaining,
            ],
            'marriage' => [
                'total' => 3,
                'used' => $balance->marriage_used ? 3 : 0,
                'remaining' => $balance->marriage_remaining,
            ],
            'maternity' => [
                'total' => 70,
                'used' => $balance->maternity_used,
                'remaining' => $balance->maternity_remaining,
            ],
            'bereavement' => [
                'used_events' => $balance->bereavement_used,
                'remaining' => $balance->bereavement_remaining,
            ],
        ];
    }

    /**
     * Annual leave earned by a given date, using day-level proration of the
     * bracket entitlement, capped at the year total. Unlike accruedAnnual()
     * (whole completed months), this projects accrual to any date — used to
     * preview the balance an employee will hold on a future leave start date.
     */
    public function accruedAnnualBy(User $user, Carbon $asOf): float
    {
        if (! $user->hire_date) {
            return 0;
        }

        $year = $asOf->year;
        $bracket = $this->annualBracketDays($user, $year);
        $hireDate = Carbon::parse($user->hire_date);
        $start = $hireDate->greaterThan(Carbon::createFromDate($year, 1, 1))
            ? $hireDate->copy()
            : Carbon::createFromDate($year, 1, 1);
        $asOf = $asOf->copy()->min(Carbon::createFromDate($year, 12, 31));

        if ($asOf->lessThan($start)) {
            return 0;
        }

        $daysInYear = $start->isLeapYear() ? 366 : 365;
        $elapsed = $start->diffInDays($asOf) + 1;

        return min(
            round(($bracket / $daysInYear) * $elapsed, 3),
            $this->calculateAnnualLeaveTotal($user, $year),
        );
    }

    /**
     * Display-only remaining balance for a leave type, optionally projected to a
     * future date ($asOf) for time-accruing types. Returns null for types that
     * carry no tracked balance (unpaid, departures, paternity). Does not affect
     * approval validation — see canTakeLeave().
     */
    public function remainingFor(User $user, HrRequestType $type, ?Carbon $asOf = null): ?float
    {
        $balance = $this->getOrCreateBalance($user);

        return match ($type) {
            HrRequestType::AnnualLeave => max(
                0,
                round($this->accruedAnnualBy($user, $asOf ?? now()) - (float) $balance->annual_used, 3),
            ),
            HrRequestType::SickLeave => $balance->sick_remaining,
            HrRequestType::MarriageLeave => $balance->marriage_remaining,
            HrRequestType::MaternityLeave => $balance->maternity_remaining,
            HrRequestType::BereavementFirst,
            HrRequestType::BereavementSecond => $balance->bereavement_remaining,
            default => null,
        };
    }
}
