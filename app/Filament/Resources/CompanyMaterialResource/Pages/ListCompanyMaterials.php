<?php

namespace App\Filament\Resources\CompanyMaterialResource\Pages;

use App\Filament\Resources\CompanyMaterialResource;
use Filament\Resources\Pages\ListRecords;

class ListCompanyMaterials extends ListRecords
{
    protected static string $resource = CompanyMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
