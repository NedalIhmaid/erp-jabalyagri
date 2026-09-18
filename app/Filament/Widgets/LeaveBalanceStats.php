<?php

namespace App\Filament\Widgets;

use App\Enums\Gender;
use App\Services\LeaveService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LeaveBalanceStats extends BaseWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user
            && ! $user->hasRole('general_manager')
            && filled($user->hire_date);
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        $balances = app(LeaveService::class)->getUserBalances($user);

        $fmt = fn ($n): string => rtrim(rtrim(number_format((float) $n, 1, '.', ''), '0'), '.');

        $color = fn (float $remaining): string => $remaining <= 0
            ? 'danger'
            : ($remaining <= 3 ? 'warning' : 'success');

        $annual = $balances['annual'];
        $sick = $balances['sick'];

        $stats = [
            Stat::make(
                __('hr.annual'),
                $fmt($annual['remaining']).' / '.$fmt($annual['total']).' '.__('hr.days'),
            )
                ->description(__('hr.earned_to_date', ['days' => $fmt($annual['accrued'])]))
                ->icon('heroicon-o-sun')
                ->color($color((float) $annual['remaining'])),

            Stat::make(
                __('hr.sick'),
                $fmt($sick['remaining']).' / '.$fmt($sick['total']).' '.__('hr.days'),
            )
                ->icon('heroicon-o-heart')
                ->color($color((float) $sick['remaining'])),
        ];

        if ($user->gender === Gender::Female) {
            $maternity = $balances['maternity'];
            $stats[] = Stat::make(
                __('hr.maternity'),
                $fmt($maternity['remaining']).' / '.$fmt($maternity['total']).' '.__('hr.days'),
            )
                ->icon('heroicon-o-face-smile')
                ->color($color((float) $maternity['remaining']));
        }

        return $stats;
    }
}
