<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\ListRoles as BaseListRoles;

class ListRoles extends BaseListRoles
{
    protected static string $resource = RoleResource::class;

    protected string $view = 'filament.resources.roles.list-roles';
}
