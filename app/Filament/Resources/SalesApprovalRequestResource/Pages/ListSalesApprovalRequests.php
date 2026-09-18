<?php

namespace App\Filament\Resources\SalesApprovalRequestResource\Pages;

use App\Enums\SalesRequestStatus;
use App\Filament\Resources\SalesApprovalRequestResource;
use App\Models\User;
use App\Services\SalesApprovalService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;

class ListSalesApprovalRequests extends ListRecords
{
    protected static string $resource = SalesApprovalRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('general.create'))
                ->after(function ($record) {
                    app(SalesApprovalService::class)->initializeStages($record);
                }),

            Action::make('export')
                ->label(__('general.export'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->modalHeading(__('general.export') . ' — ' . __('sales.title'))
                ->modalWidth('lg')
                ->form([
                    DatePicker::make('from')
                        ->label(__('general.from'))
                        ->native(false),

                    DatePicker::make('until')
                        ->label(__('general.until'))
                        ->native(false),

                    Select::make('status')
                        ->label(__('general.status'))
                        ->multiple()
                        ->options(fn () => collect(SalesRequestStatus::cases())
                            ->mapWithKeys(fn ($s) => [$s->value => $s->getLabel()])),

                    Select::make('user_id')
                        ->label(__('general.engineer'))
                        ->multiple()
                        ->searchable()
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
                    $url    = route('reports.sales.export') . '?' . http_build_query($params);
                    $this->js("window.open(" . json_encode($url) . ", '_blank')");
                }),
        ];
    }
}
