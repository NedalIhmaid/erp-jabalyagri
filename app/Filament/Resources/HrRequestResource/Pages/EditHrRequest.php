<?php

namespace App\Filament\Resources\HrRequestResource\Pages;

use App\Filament\Resources\HrRequestResource;
use App\Services\AuditLogger;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHrRequest extends EditRecord
{
    protected static string $resource = HrRequestResource::class;

    protected array $beforeState = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->using(function ($record): bool {
                    $before = app(AuditLogger::class)->hrRequestState($record->fresh(['user', 'manager']));
                    $result = $record->delete();

                    if ($result) {
                        app(AuditLogger::class)->logHrRequestDeleted($record, auth()->user(), $before);
                    }

                    return $result;
                }),
        ];
    }

    protected function beforeSave(): void
    {
        $this->beforeState = app(AuditLogger::class)->hrRequestState($this->record->fresh(['user', 'manager']));
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['duration_days'] = HrRequestResource::calculateDurationDays(
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
        );

        return $data;
    }

    protected function afterSave(): void
    {
        app(AuditLogger::class)->logHrRequestUpdated($this->record->fresh(['user', 'manager']), auth()->user(), $this->beforeState);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
