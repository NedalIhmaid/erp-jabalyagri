<?php

namespace App\Filament\Resources\ProductFamilyResource\Pages;

use App\Filament\Resources\ProductFamilyResource;
use App\Models\Product;
use App\Models\ProductFamily;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListProductFamilies extends ListRecords
{
    protected static string $resource = ProductFamilyResource::class;

    protected string $view = 'filament.resources.product-families.list-product-families';

    public ?string $catalogSearch = '';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->visible(fn () => ProductFamilyResource::canCreate())];
    }

    // The custom catalog hero in the page view owns the title; keeping the
    // framework heading would print the category name twice.
    public function getHeading(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return auth()->user()?->hasRole('engineer')
            ? null
            : __('products.families');
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getViewData(): array
    {
        return [
            'isEngineerCatalog' => auth()->user()?->hasRole('engineer') ?? false,
            'familiesCount' => ProductFamily::query()->count(),
            'varietiesCount' => Product::query()->where('is_active', true)->count(),
        ];
    }

    // The engineer catalog renders its own list (search + ruled rows) instead
    // of the Filament table; only live varieties count against the stat.
    public function getCatalogFamiliesProperty(): LengthAwarePaginator
    {
        return ProductFamily::query()
            ->withCount(['products' => fn ($q) => $q->where('products.is_active', true)])
            ->when($this->catalogSearch, fn ($query, $search) => $query->where(
                fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
            ))
            ->orderBy('name')
            ->paginate(25);
    }
}
