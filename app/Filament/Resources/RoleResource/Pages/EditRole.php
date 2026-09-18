<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Services\AdminSafetyService;
use App\Services\AuditLogger;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\EditRole as BaseEditRole;
use Filament\Actions\DeleteAction;

class EditRole extends BaseEditRole
{
    protected static string $resource = RoleResource::class;

    protected string $view = 'filament.resources.roles.edit-role';

    protected array $beforeState = [];

    protected function getActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (): bool => app(AdminSafetyService::class)->shouldHideRoleDelete($this->record))
                ->using(function ($record): bool {
                    app(AdminSafetyService::class)->ensureRoleDeletionAllowed($record);

                    $before = app(AuditLogger::class)->roleState($record->fresh('permissions'));
                    $result = $record->delete();

                    if ($result) {
                        app(AuditLogger::class)->logRoleDeleted($record, auth()->user(), $before);
                    }

                    return $result;
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        app(AdminSafetyService::class)->ensureRoleUpdateAllowed($this->record, $data);

        return parent::mutateFormDataBeforeSave($data);
    }

    protected function beforeSave(): void
    {
        $this->beforeState = app(AuditLogger::class)->roleState($this->record->fresh('permissions'));
    }

    protected function afterSave(): void
    {
        parent::afterSave();

        app(AuditLogger::class)->logRoleUpdated($this->record->fresh('permissions'), auth()->user(), $this->beforeState);
    }
}
