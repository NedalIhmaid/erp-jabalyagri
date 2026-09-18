<?php

namespace App\Filament\Widgets;

use App\Models\SalesApprovalRequest;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;

class SalesChart extends ChartWidget
{
    public function getHeading(): ?string
    {
        return __('general.sales_chart_title');
    }

    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 1;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole('general_manager') ?? false;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $data = SalesApprovalRequest::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total_amount) as total')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => __('general.request_count'),
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                    'borderColor' => 'rgba(16, 185, 129, 1)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $data->pluck('date')->map(fn ($d) => \Carbon\Carbon::parse($d)->format('M d'))->toArray(),
        ];
    }
}
