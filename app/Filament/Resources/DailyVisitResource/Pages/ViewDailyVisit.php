<?php

namespace App\Filament\Resources\DailyVisitResource\Pages;

use App\Filament\Resources\DailyVisitResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDailyVisit extends ViewRecord
{
    protected static string $resource = DailyVisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => auth()->user()->hasRole('engineer') && $record->user_id === auth()->id()),
        ];
    }
}
