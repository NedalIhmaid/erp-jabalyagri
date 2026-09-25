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

        <div class="catalog-index__content mt-30">
            <section class="catalog-section catalog-section--full">
                <h2>{{ __('products.varieties') }}</h2>

                @if ($products->isNotEmpty())
                    <div class="catalog-family-grid">
                        @foreach ($products as $product)
                            @php
                                $productImage = $product->image_path ? Storage::disk('public')->url($product->image_path) : $imageUrl;

                                // Same lead-extraction rule as the product detail hero
                                // (view-product.blade.php): first paragraph for rich-text
                                // descriptions, first line for plain text.
                                $description = trim((string) $product->description);
                                $isHtmlDescription = $description !== '' && $description !== strip_tags($description);

                                if ($isHtmlDescription) {
                                    $lead = '';
                                    if (preg_match('/<p[^>]*>(.*?)<\/p>/us', strip_tags($description, '<p>'), $match)) {
                                        $lead = trim(strip_tags($match[1]));
                                    }
                                } else {
                                    $lead = $description === '' ? '' : trim(preg_split('/\R/u', $description)[0] ?? '');
                                }

                                $lead = $lead !== '' ? \Illuminate\Support\Str::limit($lead, 200) : '';
                            @endphp
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
                                <p class="catalog-product__lead">{{ $lead ?: __('products.no_description') }}</p>
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
