<x-filament-panels::page>
    @if ($isEngineerCatalog)
        <div class="catalog-index">
            <header class="catalog-index__hero">
                <div class="catalog-index__measure">
                    <p class="catalog-hero__eyebrow">{{ __('products.catalog_eyebrow') }}</p>
                    <h1 class="catalog-hero__title">{{ __('products.families') }}</h1>
                    <p class="catalog-hero__desc">{{ __('products.catalog_index_desc') }}</p>

                    <p class="catalog-hero__stats">
                        <span class="catalog-hero__stat"><bdi>{{ $familiesCount }}</bdi> {{ __('products.stat_families_label') }}</span>
                        <span class="catalog-hero__stat-sep" aria-hidden="true"></span>
                        <span class="catalog-hero__stat"><bdi>{{ $varietiesCount }}</bdi> {{ __('products.stat_varieties_label') }}</span>
                    </p>
                </div>
            </header>

            <div class="catalog-index__content">
                <label class="catalog-search">
                    <x-heroicon-m-magnifying-glass />
                    <span class="sr-only">{{ __('products.search') }}</span>
                    <input type="search" placeholder="{{ __('products.search_placeholder') }}"
                        wire:model.live.debounce.350ms="catalogSearch">
                </label>

                <div class="catalog-index__list">
                    @forelse ($this->catalogFamilies as $family)
                        @include('filament.resources.product-families.partials.catalog-family-row', [
                            'number' => $this->catalogFamilies->firstItem() + $loop->index,
                        ])
                    @empty
                        <div class="catalog-empty">
                            <x-heroicon-o-squares-2x2 />
                            <p>{{ __('products.empty_families') }}</p>
                        </div>
                    @endforelse
                </div>

                @if ($this->catalogFamilies->hasPages())
                    <div class="catalog-index__pagination">{{ $this->catalogFamilies->links() }}</div>
                @endif
            </div>
        </div>
    @else
        {{ $this->content }}
    @endif
</x-filament-panels::page>
