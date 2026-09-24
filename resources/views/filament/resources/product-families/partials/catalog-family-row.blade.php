@php
    use App\Filament\Resources\ProductFamilyResource;
    use Illuminate\Support\Facades\Storage;

    $imageUrl = $family->image_path ? Storage::disk('public')->url($family->image_path) : null;
@endphp

<a href="{{ ProductFamilyResource::getUrl('view', ['record' => $family]) }}" class="catalog-family-row">

    <div class="catalog-family-row__photo">
        @if ($imageUrl)
            <img src="{{ $imageUrl }}" alt="{{ $family->name }}" loading="lazy">
        @else
            <x-heroicon-o-photo />
        @endif
    </div>

    <div class="catalog-family-row__body">
        <h3>{{ $family->name }}</h3>

        @if (filled($family->description))
            <p class="catalog-family-row__desc">{{ $family->description }}</p>
        @endif
    </div>

    <div class="catalog-family-row__stats">
        @if (! $family->is_active)
            <span class="catalog-stamp is-unavailable">{{ __('products.unavailable') }}</span>
        @endif

        <span class="catalog-family-row__stat">
            <bdi>{{ (int) ($family->products_count ?? 0) }}</bdi>
            <small>{{ __('products.varieties') }}</small>
        </span>
    </div>
</a>
