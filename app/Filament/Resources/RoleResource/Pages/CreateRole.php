<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Services\AuditLogger;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\CreateRole as BaseCreateRole;

class CreateRole extends BaseCreateRole
{
    protected static string $resource = RoleResource::class;

    protected string $view = 'filament.resources.roles.create-role';

    protected function afterCreate(): void
    {
        parent::afterCreate();

        app(AuditLogger::class)->logRoleCreated($this->record->fresh('permissions'), auth()->user());
    }
}
