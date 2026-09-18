<?php

namespace App\Filament\Resources\DailyVisitResource\Pages;

use App\Filament\Resources\DailyVisitResource;
use App\Models\User;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;

class ListDailyVisits extends ListRecords
{
    protected static string $resource = DailyVisitResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();

        return [
            Actions\CreateAction::make()
                ->label(__('general.create')),

            Action::make('export')
                ->label(__('general.export'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->modalHeading(__('general.export') . ' — ' . __('general.visits'))
                ->modalWidth('lg')
                ->form([
                    DatePicker::make('from')
                        ->label(__('general.from'))
                        ->native(false),

                    DatePicker::make('until')
                        ->label(__('general.until'))
                        ->native(false),

                    Select::make('user_id')
                        ->label(__('general.engineer'))
                        ->multiple()
                        ->searchable()
                        ->visible(fn () => ! auth()->user()?->hasRole('engineer'))
                        ->getSearchResultsUsing(
                            fn (string $search) => User::role('engineer')
                                ->where('name', 'like', "%{$search}%")
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->getOptionLabelsUsing(
                            fn (array $values) => User::whereIn('id', $values)
                                ->pluck('name', 'id')
                                ->toArray()
                        ),

                    Radio::make('format')
                        ->label(__('general.format'))
                        ->options(['csv' => 'CSV', 'xlsx' => 'Excel (XLSX)'])
                        ->default('xlsx')
                        ->inline(),
                ])
                ->action(function (array $data): void {
                    $params = array_filter($data, fn ($v) => filled($v));
                    $url    = route('reports.daily-visits.export') . '?' . http_build_query($params);
                    $this->js("window.open(" . json_encode($url) . ", '_blank')");
                }),
        ];
    }
}
