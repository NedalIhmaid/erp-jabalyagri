<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyMaterialResource\Pages;
use App\Models\CompanyMaterial;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CompanyMaterialResource extends Resource
{
    protected static ?string $model = CompanyMaterial::class;

    protected static ?string $slug = 'company-materials';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 3;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-archive-box';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('company.nav_group');
    }

    public static function getModelLabel(): string
    {
        return __('company.material');
    }

    public static function getPluralModelLabel(): string
    {
        return __('company.materials');
    }

    // Every panel user may browse the company materials catalog, but only
    // the general manager may change it.
    public static function canViewAny(): bool
    {
        return Auth::user() !== null;
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::user() !== null;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->hasRole('general_manager') ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::user()?->hasRole('general_manager') ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::user()?->hasRole('general_manager') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->hasRole('general_manager') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                TextInput::make('name')
                    ->label(__('company.name'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Select::make('company_material_category_id')
                    ->label(__('company.category'))
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('unit')
                    ->label(__('company.unit'))
                    ->maxLength(100),

                Toggle::make('is_active')
                    ->label(__('company.active'))
                    ->default(true)
                    ->inline(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                TextEntry::make('name')->label(__('company.name'))->weight('semibold'),
                TextEntry::make('category.name')->label(__('company.category'))->badge()->color('primary'),
                TextEntry::make('unit')->label(__('company.unit'))->placeholder('—'),
                IconEntry::make('is_active')->label(__('company.active'))->boolean(),
            ])->columns(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('category'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('company.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('category.name')
                    ->label(__('company.category'))
                    ->badge()
                    ->color(fn (CompanyMaterial $record): string => match ($record->category?->sort) {
                        1 => 'success',
                        2 => 'warning',
                        3 => 'info',
                        4 => 'danger',
                        5 => 'gray',
                        default => 'secondary',
                    })
                    ->sortable(),
                TextColumn::make('unit')
                    ->label(__('company.unit'))
                    ->placeholder('—'),
            ])
            ->defaultSort('name')
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('category')
                    ->label(__('company.category'))
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
            ], layout: \Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->recordUrl(fn (CompanyMaterial $record): string => static::getUrl('view', ['record' => $record]))
            ->actions([
                ViewAction::make(),
                EditAction::make()->visible(fn (CompanyMaterial $record) => static::canEdit($record)),
                DeleteAction::make()->visible(fn (CompanyMaterial $record) => static::canDelete($record)),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->visible(fn () => static::canDeleteAny()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanyMaterials::route('/'),
            'create' => Pages\CreateCompanyMaterial::route('/create'),
            'view' => Pages\ViewCompanyMaterial::route('/{record}'),
            'edit' => Pages\EditCompanyMaterial::route('/{record}/edit'),
        ];
    }
}
