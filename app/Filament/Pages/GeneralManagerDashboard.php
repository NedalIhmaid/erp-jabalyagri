<?php

namespace App\Filament\Pages;

use App\Enums\SalesRequestStatus;
use App\Models\ApprovalStage;
use App\Models\DailyVisit;
use App\Models\HrRequest;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use Filament\Pages\Page;

class GeneralManagerDashboard extends Page
{
    protected string $view = 'filament.pages.gm-dashboard';

    protected static ?string $slug = 'general-manager-dashboard';

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
        return 'heroicon-o-building-office';
    }

    public function getHeading(): string
    {
        return __('general.dashboard') . ' — ' . __('roles.general_manager');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('general_manager') ?? false;
    }

    public function getFooterWidgetsColumns(): int | array
    {
        return ['default' => 2];
    }

    public function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\GMOverviewWidget::class,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\SalesChart::class,
            \App\Filament\Widgets\SalesPerEngineerWidget::class,
        ];
    }
}
