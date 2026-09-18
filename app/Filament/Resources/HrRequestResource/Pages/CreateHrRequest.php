<?php

namespace App\Filament\Resources\HrRequestResource\Pages;

use App\Enums\HrRequestStatus;
use App\Filament\Resources\HrRequestResource;
use App\Services\HrRequestService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateHrRequest extends CreateRecord
{
    protected static string $resource = HrRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['manager_id'] = auth()->user()->manager_id;
        $data['status'] = HrRequestStatus::Pending;
        $data['duration_days'] = HrRequestResource::calculateDurationDays(
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
        );

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(HrRequestService::class)->submit($data, auth()->user());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
