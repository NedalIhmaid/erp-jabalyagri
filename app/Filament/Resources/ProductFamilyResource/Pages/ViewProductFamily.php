<?php

namespace App\Filament\Resources\ProductFamilyResource\Pages;

use App\Filament\Resources\ProductFamilyResource;
use App\Models\ProductFamily;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewProductFamily extends ViewRecord
{
    protected static string $resource = ProductFamilyResource::class;

    protected string $view = 'filament.resources.product-families.view-product-family';

    protected Width | string | null $maxContentWidth = Width::Full;

    // The catalog hero in the custom view owns the title and breadcrumb;
    // the framework header would print both a second time.
    public function getHeading(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return auth()->user()?->hasRole('engineer')
            ? null
            : $this->getRecord()->name;
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getViewData(): array
    {
        /** @var ProductFamily $family */
        $family = $this->getRecord();

        return [
            'isEngineerCatalog' => auth()->user()?->hasRole('engineer') ?? false,
            'family' => $family,
            'products' => $family->products()->with('productUnits')->orderBy('name')->get(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => ProductFamilyResource::canEdit($this->getRecord())),
        ];
    }
}
