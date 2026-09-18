<?php

namespace App\Filament\Pages;

use App\Enums\SalesRequestStatus;
use App\Models\DailyVisit;
use App\Models\HrRequest;
use App\Models\SalesApprovalRequest;
use App\Services\LeaveService;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EngineerDashboard extends Page
{
    protected string $view = 'filament.pages.engineer-dashboard';

    protected static ?string $slug = 'engineer-dashboard';

    public static function getNavigationLabel(): string
    {
        return __('navigation.dashboard');
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
        return 'heroicon-o-home';
    }

    public function getHeading(): string
    {
        return __('general.dashboard');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('engineer') ?? false;
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return ['default' => 1];
    }

    public function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\EngineerDashboardStats::class,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [];
    }
}
