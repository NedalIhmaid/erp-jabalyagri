<?php

namespace App\Filament\Widgets;

use App\Enums\SalesRequestStatus;
use App\Models\DailyVisit;
use App\Models\SalesApprovalRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EngineerDashboardStats extends BaseWidget
{
    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('engineer') ?? false;
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        $thisMonthVisits = DailyVisit::where('user_id', $user->id)
            ->whereMonth('visit_date', now()->month)
            ->whereYear('visit_date', now()->year)
            ->count();

        $returnedRequests = SalesApprovalRequest::where('user_id', $user->id)
            ->where('status', SalesRequestStatus::Returned)
            ->count();

        $pendingHr = \App\Models\HrRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        return [
            Stat::make(__('general.visits_this_month'), $thisMonthVisits)
                ->icon('heroicon-o-map-pin')
                ->color('primary'),

            Stat::make(__('general.needs_review'), $returnedRequests)
                ->description(__('general.review_required'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color($returnedRequests > 0 ? 'danger' : 'success'),

            Stat::make(__('general.pending_leave'), $pendingHr)
                ->icon('heroicon-o-document-text')
                ->color($pendingHr > 0 ? 'warning' : 'success'),
        ];
    }
}
