<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductFamilyResource\Pages;
use App\Models\ProductFamily;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductFamilyResource extends Resource
{
    protected static ?string $model = ProductFamily::class;
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'product-categories';

    public static function getNavigationLabel(): string
    {
        return auth()->user()?->hasRole('engineer')
            ? __('products.products')
            : __('products.families');
    }
    public static function getModelLabel(): string { return __('products.family'); }
    public static function getPluralModelLabel(): string { return __('products.families'); }
    public static function getNavigationIcon(): ?string { return 'heroicon-o-squares-2x2'; }
    public static function getNavigationGroup(): ?string { return __('navigation.catalog'); }
    public static function canViewAny(): bool { return ProductResource::canViewAny(); }
    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool { return static::canViewAny(); }
    public static function canCreate(): bool { return ProductResource::canCreate(); }
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return ProductResource::canEdit($record); }
    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return ProductResource::canDelete($record); }
    public static function canDeleteAny(): bool { return ProductResource::canDeleteAny(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('products.family_info'))
                ->icon('heroicon-o-tag')
                ->description(__('products.category_form_help'))
                ->schema([
                    TextInput::make('name')->label(__('products.family_name'))->required()->maxLength(255),
                    Toggle::make('is_active')->label(__('products.active'))->default(true)->inline(),
                    Textarea::make('description')->label(__('products.description'))->rows(5)->maxLength(3000)->columnSpanFull(),
                    FileUpload::make('image_path')->label(__('products.image'))->image()->imageEditor()
                        ->disk('public')->directory('product-families')->visibility('public')->maxSize(8192)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
        ]);
    }

    // Only the admin audience renders this table; the engineer catalog list
    // page builds its own rows and never asks for the table.
    public static function table(Table $table): Table
    {
        $table->modifyQueryUsing(fn ($query) => $query->withCount('products'));

        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label(__('products.image'))
                    ->disk('public')
                    ->square()
                    ->size(48),
                TextColumn::make('name')
                    ->label(__('products.family_name'))
                    ->description(fn (ProductFamily $record): ?string => $record->description)
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('products_count')
                    ->label(__('products.varieties_count'))
                    ->counts('products')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('products.updated_at'))
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->recordUrl(fn (ProductFamily $record): string => static::getUrl('view', ['record' => $record]))
            ->actions([
                ViewAction::make(),
                EditAction::make()->visible(fn (ProductFamily $record) => static::canEdit($record)),
                DeleteAction::make()->visible(fn (ProductFamily $record) => static::canDelete($record)),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()->visible(fn () => static::canDeleteAny())]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        if (! (auth()->user()?->hasRole('engineer') ?? false)) {
            return $schema->schema([
                Section::make(__('products.family_info'))
                    ->schema([
                        ImageEntry::make('image_path')
                            ->label(__('products.image'))
                            ->disk('public')
                            ->imageWidth(120)
                            ->imageHeight(120)
                            ->extraImgAttributes(['class' => 'rounded-lg object-cover']),
                        TextEntry::make('name')
                            ->label(__('products.family_name'))
                            ->weight('bold'),
                        TextEntry::make('description')
                            ->label(__('products.description'))
                            ->placeholder('—'),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 3,
                    ])
                    ->columnSpanFull(),
                Section::make(__('products.varieties'))
                    ->description(__('products.varieties_help'))
                    ->schema([
                        RepeatableEntry::make('products')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make(__('products.image')),
                                TableColumn::make(__('products.variety_name')),
                                TableColumn::make(__('products.sku')),
                            ])
                            ->schema([
                                ImageEntry::make('image_path')
                                    ->hiddenLabel()
                                    ->disk('public')
                                    ->imageWidth(44)
                                    ->imageHeight(44)
                                    ->extraImgAttributes(['class' => 'rounded-md object-cover']),
                                TextEntry::make('name')
                                    ->hiddenLabel()
                                    ->weight('semibold')
                                    ->url(fn ($record): string => ProductResource::getUrl('view', ['record' => $record])),
                                TextEntry::make('sku')
                                    ->hiddenLabel()
                                    ->placeholder('—'),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
        }

        return $schema->schema([
            Section::make(__('products.family_info'))->icon('heroicon-o-tag')->schema([
                ImageEntry::make('image_path')->label(__('products.image'))->disk('public')
                    ->imageWidth(220)->imageHeight(130)
                    ->extraImgAttributes(['class' => 'rounded-xl object-cover'])
                    ->columnSpan(1),
                TextEntry::make('name')->label(__('products.family_name'))->weight('bold')->size('lg'),
                IconEntry::make('is_active')->label(__('products.active'))->boolean(),
                TextEntry::make('description')->label(__('products.description'))->placeholder('—')->columnSpan(2),
            ])->columns(3)->columnSpanFull(),
            Section::make(__('products.varieties'))->icon('heroicon-o-rectangle-stack')->description(__('products.varieties_help'))->schema([
                RepeatableEntry::make('products')->hiddenLabel()->schema([
                    ImageEntry::make('image_path')->hiddenLabel()->disk('public')
                        ->imageWidth('100%')->imageHeight(150)
                        ->extraImgAttributes(['class' => 'rounded-xl object-cover'])
                        ->url(fn ($record): string => ProductResource::getUrl('view', ['record' => $record])),
                    TextEntry::make('name')->label(__('products.variety_name'))->weight('bold')->size('lg')
                        ->url(fn ($record): string => ProductResource::getUrl('view', ['record' => $record])),
                    TextEntry::make('sku')->label(__('products.sku'))->badge()->color('gray'),
                    TextEntry::make('description')->label(__('products.description'))->placeholder('—')->columnSpan(3),
                    TextEntry::make('pdf_url')->label(__('products.pdf_url'))
                        ->formatStateUsing(fn () => __('products.open_pdf'))
                        ->url(fn ($record): ?string => $record->pdf_url)->openUrlInNewTab()->placeholder('—'),
                    TextEntry::make('google_drive_url')->label(__('products.google_drive_url'))
                        ->formatStateUsing(fn () => __('products.open_images'))
                        ->url(fn ($record): ?string => $record->google_drive_url)->openUrlInNewTab()->placeholder('—'),
                    IconEntry::make('is_active')->label(__('products.active'))->boolean(),
                    TextEntry::make('view_details')
                        ->label('')
                        ->getStateUsing(fn (): string => __('products.view_details'))
                        ->icon('heroicon-m-arrow-left')
                        ->color('primary')
                        ->weight('semibold')
                        ->url(fn ($record): string => ProductResource::getUrl('view', ['record' => $record]))
                        ->columnSpan(3),
                    RepeatableEntry::make('productUnits')->label(__('products.selling_units'))
                        ->table([
                            TableColumn::make(__('products.unit_label')),
                            TableColumn::make(__('products.price')),
                        ])
                        ->schema([
                            TextEntry::make('label')->hiddenLabel(),
                            TextEntry::make('price')->hiddenLabel()->money('JOD')->color('success')->weight('bold'),
                        ])->columnSpan(3),
                ])->columns(3)->grid(['md' => 2])->columnSpanFull(),
            ])->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductFamilies::route('/'),
            'create' => Pages\CreateProductFamily::route('/create'),
            'view' => Pages\ViewProductFamily::route('/{record}'),
            'edit' => Pages\EditProductFamily::route('/{record}/edit'),
        ];
    }
}
