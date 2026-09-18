<?php

namespace App\Filament\Resources\DailyVisitResource\Pages;

use App\Filament\Resources\DailyVisitResource;
use App\Models\Farmer;
use Filament\Resources\Pages\CreateRecord;

class CreateDailyVisit extends CreateRecord
{
    protected static string $resource = DailyVisitResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        if (! empty($data['farmer_id'])) {
            $data['client_name'] = Farmer::find($data['farmer_id'])?->name ?? ($data['client_name'] ?? null);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
