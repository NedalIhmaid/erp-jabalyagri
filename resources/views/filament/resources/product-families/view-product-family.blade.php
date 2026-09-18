@php
    use Illuminate\Support\Facades\Storage;
    $imageUrl = $family->image_path ? Storage::disk('public')->url($family->image_path) : null;
@endphp

<x-filament-panels::page>
    @if (! $isEngineerCatalog)
        {{ $this->infolist }}
    @else
    <article class="catalog-product">
        <nav class="catalog-breadcrumb" aria-label="{{ __('products.back_to_products') }}">
            <a href="{{ \App\Filament\Resources\ProductFamilyResource::getUrl('index') }}">{{ __('products.families') }}</a>
            <x-heroicon-m-chevron-left />
            <span>{{ $family->name }}</span>
        </nav>

        <div class="catalog-family__body">
            <section class="catalog-section catalog-section--full">
                <h2>{{ __('products.varieties') }}</h2>

                @if ($products->isNotEmpty())
                    <div class="catalog-family-grid">
                        @foreach ($products as $product)
                            @php($productImage = $product->image_path ? Storage::disk('public')->url($product->image_path) : $imageUrl)
                            <a
                                href="{{ \App\Filament\Resources\ProductResource::getUrl('view', ['record' => $product]) }}"
                                class="catalog-family-card @if (! $product->is_active) is-inactive @endif"
                            >
                                <div class="catalog-family-card__photo">
                                    @if ($productImage)
                                        <img src="{{ $productImage }}" alt="{{ $product->name }}" loading="lazy">
                                    @else
                                        <x-heroicon-o-photo />
                                    @endif
                                    @if (! $product->is_active)
                                        <span class="catalog-family-card__inactive">{{ __('products.unavailable') }}</span>
                                    @endif
                                </div>
                                <div class="catalog-family-card__body">
                                    <h3>{{ $product->name }}</h3>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="catalog-empty">
                        <x-heroicon-o-rectangle-stack />
                        <p>{{ __('products.empty_varieties') }}</p>
                    </div>
                @endif
            </section>
        </div>
    </article>
    @endif
</x-filament-panels::page>
