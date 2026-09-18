<?php

namespace App\Filament\Pages;

use App\Providers\Filament\AdminPanelProvider;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount()
    {
        $target = AdminPanelProvider::resolveHomeUrl();

        if ($target !== request()->url() && $target !== request()->getRequestUri()) {
            return redirect($target);
        }
    }

    public function getWidgets(): array
    {
        return [];
    }
}
