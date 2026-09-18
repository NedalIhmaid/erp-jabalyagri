<?php

namespace App\Filament\Widgets;

use App\Enums\SalesRequestStatus;
use App\Models\SalesApprovalRequest;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class SalesPerEngineerWidget extends Widget
{
    protected static ?int $sort = 1;

    protected string $view = 'filament.widgets.sales-per-engineer';

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('general_manager') ?? false;
    }

    public function getRows(): Collection
    {
        return SalesApprovalRequest::query()
            ->selectRaw('user_id, COUNT(*) as requests_count, SUM(total_amount) as total_sales')
            ->where('status', SalesRequestStatus::Approved)
            ->groupBy('user_id')
            ->with('user:id,name')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get();
    }
}
