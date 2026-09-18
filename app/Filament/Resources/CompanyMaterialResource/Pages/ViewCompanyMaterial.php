<?php

namespace App\Filament\Resources\CompanyMaterialResource\Pages;

use App\Filament\Resources\CompanyMaterialResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCompanyMaterial extends ViewRecord
{
    protected static string $resource = CompanyMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
