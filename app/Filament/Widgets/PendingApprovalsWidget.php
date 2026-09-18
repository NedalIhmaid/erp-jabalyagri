<?php

namespace App\Filament\Widgets;

use App\Enums\ApprovalAction;
use App\Enums\SalesRequestStatus;
use App\Models\ApprovalStage;
use App\Models\SalesApprovalRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendingApprovalsWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole([
            'warehouse_keeper',
            'purchasing_manager',
            'financial_manager',
        ]) ?? false;
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        $pendingForMe = ApprovalStage::whereIn(
            'role',
            $user->roles()->pluck('name')
        )
            ->where(function ($query) use ($user) {
                $query->where('role', '!=', 'warehouse_keeper')
                    ->orWhere('approver_id', $user->id);
            })
            ->where('action', ApprovalAction::Pending)
            ->whereHas('salesApprovalRequest', fn ($query) => $query
                ->where('status', SalesRequestStatus::InProgress)
                ->whereColumn('sales_approval_requests.current_stage', 'approval_stages.stage_number'))
            ->count();

        $approvedToday = SalesApprovalRequest::where('status', SalesRequestStatus::Approved)
            ->whereDate('updated_at', today())
            ->count();

        return [
            Stat::make(__('general.awaiting_your_approval'), $pendingForMe)
                ->description($pendingForMe > 0 ? __('general.requires_action') : __('general.no_pending_requests'))
                ->icon('heroicon-o-clock')
                ->color($pendingForMe > 0 ? 'danger' : 'success'),

            Stat::make(__('general.approved_today'), $approvedToday)
                ->description(__('general.final_approvals_today'))
                ->icon('heroicon-o-check-badge')
                ->color('success'),
        ];
    }
}
