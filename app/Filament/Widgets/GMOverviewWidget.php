<?php

namespace App\Filament\Widgets;

use App\Enums\SalesRequestStatus;
use App\Models\DailyVisit;
use App\Models\HrRequest;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GMOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('general_manager') ?? false;
    }

    protected function getStats(): array
    {
        $totalEngineers = User::role('engineer')->where('is_active', true)->count();
        $totalAmount = SalesApprovalRequest::where('status', SalesRequestStatus::Approved)->sum('total_amount');
        $thisMonthAmount = SalesApprovalRequest::where('status', SalesRequestStatus::Approved)
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount');
        $pendingSales = SalesApprovalRequest::whereIn('status', [SalesRequestStatus::Pending, SalesRequestStatus::InProgress])->count();
        $pendingHR = HrRequest::whereIn('status', ['pending', 'manager_approved'])->count();
        $visitsThisMonth = DailyVisit::whereMonth('visit_date', now()->month)
            ->whereYear('visit_date', now()->year)
            ->count();

        return [
            Stat::make(__('general.active_engineers'), $totalEngineers)
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make(__('general.total_sales'), 'JOD ' . number_format($totalAmount, 2))
                ->description(__('general.this_month_prefix') . ' JOD ' . number_format($thisMonthAmount, 2))
                ->icon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make(__('general.pending_sales_requests'), $pendingSales)
                ->icon('heroicon-o-document-check')
                ->color($pendingSales > 0 ? 'warning' : 'success'),

            Stat::make(__('general.visits_this_month'), $visitsThisMonth)
                ->icon('heroicon-o-map-pin')
                ->color('primary'),

            Stat::make(__('general.pending_hr_requests'), $pendingHR)
                ->icon('heroicon-o-document-text')
                ->color($pendingHR > 0 ? 'warning' : 'success'),
        ];
    }
}
