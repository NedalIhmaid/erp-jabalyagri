<?php

namespace App\Filament\Pages;

use App\Enums\SalesRequestStatus;
use App\Models\ApprovalStage;
use App\Models\DailyVisit;
use App\Models\HrRequest;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class ApprovalDashboard extends Page
{
    protected string $view = 'filament.pages.approval-dashboard';

    protected static ?string $slug = 'approval-dashboard';

    public static function getNavigationLabel(): string
    {
        return __('navigation.approvals');
    }

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    public static function getNavigationSort(): int
    {
        return 0;
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-clipboard-document-check';
    }

    public function getHeading(): string
    {
        return __('general.dashboard') . ' — ' . __('general.approvals');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            'warehouse_keeper',
            'sales_manager',
            'purchasing_manager',
            'financial_manager',
        ]) ?? false;
    }

    public function getFooterWidgetsColumns(): int
    {
        return 1;
    }

    public function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\PendingApprovalsWidget::class,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [];
    }

    public function getMyEngineers(): Collection
    {
        $user = auth()->user();

        if (! $user?->hasRole('sales_manager')) {
            return collect();
        }

        return User::query()
            ->where('manager_id', $user->id)
            ->role('engineer')
            ->withCount([
                'dailyVisits as visits_this_month_count' => fn ($q) => $q
                    ->whereMonth('visit_date', now()->month)
                    ->whereYear('visit_date', now()->year),
                'salesRequests as pending_sales_count' => fn ($q) => $q
                    ->whereIn('status', [SalesRequestStatus::Pending, SalesRequestStatus::InProgress]),
            ])
            ->orderBy('name')
            ->get();
    }
}
