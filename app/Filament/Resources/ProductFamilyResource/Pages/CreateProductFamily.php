<?php

namespace App\Filament\Resources\ProductFamilyResource\Pages;

use App\Filament\Resources\ProductFamilyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductFamily extends CreateRecord
{
    protected static string $resource = ProductFamilyResource::class;
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('index'); }
}
