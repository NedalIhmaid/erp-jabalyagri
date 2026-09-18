<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected string $view = 'filament.resources.products.view-product';

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
        /** @var Product $product */
        $product = $this->getRecord()->loadMissing(['family', 'productUnits']);

        return [
            'isEngineerCatalog' => auth()->user()?->hasRole('engineer') ?? false,
            'product' => $product,
            'relatedProducts' => $product->family
                ? $product->family->products()
                    ->whereKeyNot($product->getKey())
                    ->where('is_active', true)
                    ->with('family')
                    ->limit(4)
                    ->get()
                : collect(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (): bool => ProductResource::canEdit($this->getRecord())),
        ];
    }
}
