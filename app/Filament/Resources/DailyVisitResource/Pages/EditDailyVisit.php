<?php

namespace App\Filament\Resources\DailyVisitResource\Pages;

use App\Filament\Resources\DailyVisitResource;
use App\Models\Farmer;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDailyVisit extends EditRecord
{
    protected static string $resource = DailyVisitResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['farmer_id'])) {
            $data['client_name'] = Farmer::find($data['farmer_id'])?->name ?? ($data['client_name'] ?? null);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
