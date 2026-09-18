@php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $imageUrl = $product->image_path ? Storage::disk('public')->url($product->image_path) : null;
    $familyImageUrl = $product->family?->image_path ? Storage::disk('public')->url($product->family->image_path) : null;
    $displayImage = $imageUrl ?: $familyImageUrl;
    $activeUnits = $product->productUnits->where('is_active', true);

    // The description may be rich text authored in the product form (HTML)
    // or legacy plain text. Rich text: the first paragraph becomes the lead
    // and the sanitized remainder renders as the overview body. Plain text:
    // the first line leads and the prose component structures the rest.
    $description = trim((string) $product->description);
    $isHtmlDescription = $description !== '' && $description !== strip_tags($description);

    if ($isHtmlDescription) {
        $allowed = '<p><h3><h4><ul><ol><li><strong><em><b><i><u><s><a><br>';
        $safeHtml = trim(strip_tags($description, $allowed));
        // Neutralize javascript: targets that strip_tags leaves on kept tags.
        $safeHtml = preg_replace('/\shref\s*=\s*([\'"])\s*javascript:[^"\']*\1/i', ' href="#"', $safeHtml);

        $overviewHtml = $safeHtml;
        $lead = '';
        if (preg_match('/<p[^>]*>(.*?)<\/p>/us', $safeHtml, $match)) {
            $lead = trim(strip_tags($match[1]));
            $overviewHtml = trim((string) preg_replace('/<p[^>]*>.*?<\/p>/us', '', $safeHtml, 1));
        }
        if (mb_strlen($lead) > 260) {
            $lead = Str::limit($lead, 260);
        }
        $overview = trim(strip_tags($overviewHtml));
    } else {
        // The hero carries the opening line only; the rest of the copy belongs
        // in the overview section, where it gets a reading measure and structure.
        $firstLine = $description === '' ? '' : (preg_split('/\R/u', $description)[0] ?? '');
        $leadIsClipped = mb_strlen($firstLine) > 260;
        $lead = $leadIsClipped ? Str::limit($firstLine, 260) : $firstLine;
        $overview = $leadIsClipped
            ? $description
            : trim(mb_substr($description, mb_strlen($firstLine)));
    }
@endphp

<x-filament-panels::page>
    @if (! $isEngineerCatalog)
        {{ $this->infolist }}
    @else
    <article class="catalog-product">
        <nav class="catalog-breadcrumb" aria-label="{{ __('products.back_to_products') }}">
            <a href="{{ \App\Filament\Resources\ProductFamilyResource::getUrl('index') }}">{{ __('products.families') }}</a>
            <x-heroicon-m-chevron-left />
            @if ($product->family)
                <a href="{{ \App\Filament\Resources\ProductFamilyResource::getUrl('view', ['record' => $product->family]) }}">{{ $product->family->name }}</a>
                <x-heroicon-m-chevron-left />
            @endif
            <span>{{ $product->name }}</span>
        </nav>

        <header class="catalog-product__hero">
            <div class="catalog-product__intro">
                <span class="catalog-stamp {{ $product->is_active ? 'is-available' : 'is-unavailable' }}">{{ $product->is_active ? __('products.available') : __('products.unavailable') }}</span>

                @if ($product->family)
                    <a class="catalog-hero__eyebrow catalog-hero__eyebrow--link" href="{{ \App\Filament\Resources\ProductFamilyResource::getUrl('view', ['record' => $product->family]) }}">{{ $product->family->name }}</a>
                @endif

                <h1>{{ $product->name }}</h1>

                @if ($product->sku)
                    <p class="catalog-product__sku" x-data="{ copied: false }">
                        <span>{{ __('products.sku') }}</span>
                        <bdi>{{ $product->sku }}</bdi>
                        <button
                            type="button"
                            class="catalog-copy-btn"
                            data-sku="{{ $product->sku }}"
                            title="{{ __('products.copy_sku') }}"
                            aria-label="{{ __('products.copy_sku') }}"
                            @click="navigator.clipboard.writeText($el.dataset.sku); copied = true; setTimeout(() => copied = false, 1600)"
                        >
                            <x-heroicon-m-clipboard-document x-show="! copied" x-cloak />
                            <x-heroicon-m-check x-cloak x-show="copied" class="is-copied" />
                        </button>
                    </p>
                @endif

                <p class="catalog-product__lead">{{ $lead ?: __('products.no_description') }}</p>

                <div class="catalog-product__links">
                    @if ($product->pdf_url)
                        <a class="catalog-button catalog-button--filled" href="{{ $product->pdf_url }}" target="_blank" rel="noopener noreferrer"><x-heroicon-o-arrow-down-tray />{{ __('products.open_pdf') }}</a>
                    @else
                        <span class="catalog-button catalog-button--filled is-disabled" title="{{ __('products.not_available') }}" aria-disabled="true"><x-heroicon-o-arrow-down-tray />{{ __('products.open_pdf') }}</span>
                    @endif

                    @if ($product->google_drive_url)
                        <a class="catalog-button" href="{{ $product->google_drive_url }}" target="_blank" rel="noopener noreferrer"><x-heroicon-o-photo />{{ __('products.open_images') }}</a>
                    @else
                        <span class="catalog-button is-disabled" title="{{ __('products.not_available') }}" aria-disabled="true"><x-heroicon-o-photo />{{ __('products.open_images') }}</span>
                    @endif
                </div>
            </div>

            <figure class="catalog-product__photo">
                @if ($displayImage)
                    <img src="{{ $displayImage }}" alt="{{ $product->name }}">
                @else
                    <div class="catalog-product__photo-empty"><x-heroicon-o-photo /></div>
                @endif
            </figure>
        </header>

        <div @class(['catalog-product__body', 'catalog-product__body--compact' => blank($overview)])>
            @if (filled($overview))
                <div class="catalog-product__content">
                    <section class="catalog-section">
                        <h2>{{ __('products.overview') }}</h2>
                        @if ($isHtmlDescription)
                            <div class="catalog-prose">{!! $overviewHtml !!}</div>
                        @else
                            <x-catalog-prose :text="$overview" />
                        @endif
                    </section>
                </div>
            @endif

            <aside class="catalog-product__facts">
                <div class="catalog-facts__heading">
                    <h2>{{ __('products.available_packages') }}</h2>
                </div>
                <div class="catalog-units">
                    @forelse ($activeUnits->sortBy('price') as $unit)
                        <div class="catalog-unit">
                            <div>
                                <strong>{{ $unit->label }}</strong>
                                <span>{{ $unit->unit_type?->getLabel() }}@if($unit->unit_value) · <bdi>{{ $unit->unit_value }}</bdi>@endif</span>
                            </div>
                        </div>
                    @empty
                        <p class="catalog-units__empty">—</p>
                    @endforelse
                </div>
            </aside>
        </div>

        @if ($relatedProducts->isNotEmpty())
            <section class="catalog-section catalog-section--full catalog-product__related">
                <h2>{{ __('products.related_varieties') }}</h2>
                <div class="catalog-related">
                    @foreach ($relatedProducts as $related)
                        @php($relatedImage = $related->image_path ? Storage::disk('public')->url($related->image_path) : $familyImageUrl)
                        <a href="{{ \App\Filament\Resources\ProductResource::getUrl('view', ['record' => $related]) }}" class="catalog-related__item">
                            <div class="catalog-related__photo">
                                @if ($relatedImage)<img src="{{ $relatedImage }}" alt="{{ $related->name }}" loading="lazy">@else<x-heroicon-o-photo />@endif
                            </div>
                            <div class="catalog-related__body">
                                <h3>{{ $related->name }}</h3>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </article>
    @endif
</x-filament-panels::page>
