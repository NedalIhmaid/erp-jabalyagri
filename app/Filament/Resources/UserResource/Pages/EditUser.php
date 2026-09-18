<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Services\AdminSafetyService;
use App\Services\AuditLogger;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected string $view = 'filament.resources.users.edit-user';

    protected array $beforeState = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->hidden(fn (): bool => app(AdminSafetyService::class)->shouldHideUserDelete($this->record, auth()->user()))
                ->using(function ($record): bool {
                    app(AdminSafetyService::class)->ensureUserDeletionAllowed($record, auth()->user());

                    $before = app(AuditLogger::class)->userState($record->fresh('roles'));
                    $result = $record->delete();

                    if ($result) {
                        app(AuditLogger::class)->logUserDeleted($record, auth()->user(), $before);
                    }

                    return $result;
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        app(AdminSafetyService::class)->ensureUserUpdateAllowed($this->record, $data, auth()->user());

        return $data;
    }

    protected function beforeSave(): void
    {
        $this->beforeState = app(AuditLogger::class)->userState($this->record->fresh('roles'));
    }

    protected function afterSave(): void
    {
        app(AuditLogger::class)->logUserUpdated($this->record->fresh('roles'), auth()->user(), $this->beforeState);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
