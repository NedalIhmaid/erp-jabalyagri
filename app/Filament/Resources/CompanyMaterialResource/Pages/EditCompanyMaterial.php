<?php

namespace App\Filament\Resources\CompanyMaterialResource\Pages;

use App\Filament\Resources\CompanyMaterialResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompanyMaterial extends EditRecord
{
    protected static string $resource = CompanyMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
