<?php

namespace App\Filament\Resources;

use App\Enums\ProductUnitType;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    private const CATALOG_VIEWER_ROLES = [
        'engineer',
        'warehouse_keeper',
        'sales_manager',
        'purchasing_manager',
        'financial_manager',
        'general_manager',
    ];

    protected static ?string $model = Product::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'sku', 'family.name'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            __('products.sku') => $record->sku ?? '—',
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.catalog');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return ! (auth()->user()?->hasRole('engineer') ?? false);
    }

    public static function getNavigationLabel(): string
    {
        return __('products.products');
    }

    public static function getModelLabel(): string
    {
        return __('products.product');
    }

    public static function getPluralModelLabel(): string
    {
        return __('products.products');
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-cube';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(self::CATALOG_VIEWER_ROLES) ?? false;
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['general_manager', 'warehouse_keeper']) ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['general_manager', 'warehouse_keeper']) ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->hasRole('general_manager') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasRole('general_manager') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('products.product_info'))
                    ->icon('heroicon-o-cube')
                    ->schema([
                        Select::make('product_family_id')
                            ->relationship('family', 'name')
                            ->label(__('products.family'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2),

                        TextInput::make('name')
                            ->label(__('products.variety_name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make('sku')
                            ->label(__('products.sku'))
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->placeholder('مثال: PEST-001')
                            ->columnSpan(2),

                        FileUpload::make('image_path')
                            ->label(__('products.image'))
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('product-varieties')
                            ->visibility('public')
                            ->maxSize(8192)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->columnSpan(2),

                        RichEditor::make('description')
                            ->label(__('products.description'))
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'strike', 'link',
                                'h3', 'bulletList', 'orderedList',
                                'undo', 'redo',
                            ])
                            ->maxLength(20000)
                            ->columnSpan(2),

                        TextInput::make('pdf_url')
                            ->label(__('products.pdf_url'))
                            ->url()
                            ->maxLength(2048)
                            ->columnSpan(2),

                        TextInput::make('google_drive_url')
                            ->label(__('products.google_drive_url'))
                            ->url()
                            ->maxLength(2048)
                            ->columnSpan(2),

                        Toggle::make('is_active')
                            ->label(__('products.active_product'))
                            ->default(true)
                            ->inline()
                            ->columnSpan(2),
                    ])->columns(2)->columnSpanFull(),

                Section::make(__('products.selling_units'))
                    ->icon('heroicon-o-squares-2x2')
                    ->description(__('products.selling_units_desc'))
                    ->schema([
                        Repeater::make('productUnits')
                            ->relationship()
                            ->hiddenLabel()
                            ->itemLabel(fn (array $state): ?string => filled($state['label'])
                                ? $state['label'] . (filled($state['price']) ? ' — ' . number_format((float) $state['price'], 2) . ' JOD' : '')
                                : null)
                            ->collapsible()
                            ->schema([
                                // Row 1: name, price, active
                                TextInput::make('label')
                                    ->label(__('products.unit_label'))
                                    ->placeholder(__('products.unit_label_ph'))
                                    ->required()
                                    ->maxLength(100)
                                    ->columnSpan(2),

                                TextInput::make('price')
                                    ->label(__('products.price'))
                                    ->numeric()
                                    ->prefix('JOD')
                                    ->required()
                                    ->columnSpan(1),

                                Toggle::make('is_active')
                                    ->label(__('products.active'))
                                    ->default(true)
                                    ->inline()
                                    ->columnSpan(1),

                                // Row 2: type + quantity value
                                Select::make('unit_type')
                                    ->label(__('products.unit_type'))
                                    ->options(fn (): array => collect(ProductUnitType::cases())
                                        ->mapWithKeys(fn ($t) => [$t->value => $t->getLabel()])
                                        ->all())
                                    ->required()
                                    ->columnSpan(2),

                                TextInput::make('unit_value')
                                    ->label(__('products.unit_value'))
                                    ->placeholder(__('products.unit_value_ph'))
                                    ->numeric()
                                    ->nullable()
                                    ->columnSpan(2),
                            ])
                            ->columns(4)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel(__('products.add_unit'))
                            ->reorderable(),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->with(['family', 'productUnits']))
            ->columns([
                ImageColumn::make('image_path')
                    ->label(__('products.image'))
                    ->disk('public')
                    ->circular(),

                TextColumn::make('family.name')
                    ->label(__('products.family'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label(__('products.variety'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('sku')
                    ->label(__('products.sku'))
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('productUnits')
                    ->label(__('products.selling_units'))
                    ->getStateUsing(fn (\App\Models\Product $record): array => $record->productUnits
                        ->where('is_active', true)
                        ->map(fn ($u) => $u->label . ' — ' . number_format((float) $u->price, 2) . ' JOD')
                        ->all()
                    )
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->wrap(),

                IconColumn::make('is_active')
                    ->label(__('products.active'))
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('general.active_status')),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Product $record): bool => static::canEdit($record)),
                DeleteAction::make()
                    ->visible(fn (Product $record): bool => static::canDelete($record)),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => static::canDeleteAny()),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('products.product_info'))
                    ->icon('heroicon-o-cube')
                    ->schema([
                        ImageEntry::make('image_path')
                            ->label(__('products.image'))
                            ->disk('public')
                            ->getStateUsing(fn (Product $record): ?string => $record->image_path ?: $record->family?->image_path)
                            ->imageWidth(120)
                            ->imageHeight(120)
                            ->extraImgAttributes(['class' => 'rounded-lg object-cover']),
                        TextEntry::make('family.name')
                            ->label(__('products.family'))
                            ->url(fn (Product $record): ?string => $record->family
                                ? ProductFamilyResource::getUrl('view', ['record' => $record->family])
                                : null),
                        TextEntry::make('name')
                            ->label(__('products.variety_name'))
                            ->weight('semibold'),
                        TextEntry::make('sku')
                            ->label(__('products.sku'))
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('description')
                            ->label(__('products.description'))
                            ->html()
                            ->prose()
                            ->columnSpan(['md' => 4])
                            ->placeholder('—'),
                        TextEntry::make('pdf_url')
                            ->label(__('products.pdf_url'))
                            ->formatStateUsing(fn () => __('products.open_pdf'))
                            ->icon('heroicon-o-arrow-down-tray')
                            ->badge()
                            ->color('primary')
                            ->url(fn ($record): ?string => $record->pdf_url)
                            ->openUrlInNewTab()
                            ->columnSpan(['md' => 2])
                            ->placeholder('—'),
                        TextEntry::make('google_drive_url')
                            ->label(__('products.google_drive_url'))
                            ->formatStateUsing(fn () => __('products.open_images'))
                            ->icon('heroicon-o-photo')
                            ->badge()
                            ->color('primary')
                            ->url(fn ($record): ?string => $record->google_drive_url)
                            ->openUrlInNewTab()
                            ->columnSpan(['md' => 2])
                            ->placeholder('—'),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 4,
                    ])
                    ->columnSpanFull(),

                Section::make(__('products.selling_units'))
                    ->icon('heroicon-o-squares-2x2')
                    ->schema([
                        RepeatableEntry::make('productUnits')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make(__('products.unit_label')),
                                TableColumn::make(__('products.unit_type_label')),
                                TableColumn::make(__('products.price')),
                            ])
                            ->schema([
                                TextEntry::make('label')
                                    ->hiddenLabel()
                                    ->weight('medium'),
                                TextEntry::make('unit_type')
                                    ->hiddenLabel()
                                    ->formatStateUsing(fn ($state) => $state?->getLabel())
                                    ->badge()
                                    ->color('gray'),
                                TextEntry::make('price')
                                    ->hiddenLabel()
                                    ->money('JOD')
                                    ->color('success')
                                    ->weight('semibold'),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
