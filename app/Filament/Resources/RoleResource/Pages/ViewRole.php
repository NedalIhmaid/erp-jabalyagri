<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\ViewRole as BaseViewRole;

class ViewRole extends BaseViewRole
{
    protected static string $resource = RoleResource::class;

    protected string $view = 'filament.resources.roles.view-role';
}
