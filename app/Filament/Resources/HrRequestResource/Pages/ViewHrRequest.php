<?php

namespace App\Filament\Resources\HrRequestResource\Pages;

use App\Filament\Resources\HrRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewHrRequest extends ViewRecord
{
    protected static string $resource = HrRequestResource::class;

    protected string $view = 'filament.resources.hr-requests.view-hr-request';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => auth()->user()?->id === $record->user_id && $record->status === \App\Enums\HrRequestStatus::Pending),
        ];
    }
}
