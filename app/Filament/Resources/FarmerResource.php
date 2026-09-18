<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FarmerResource\Pages;
use App\Models\Farmer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FarmerResource extends Resource
{
    protected static ?string $model = Farmer::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.admin');
    }

    public static function getNavigationLabel(): string
    {
        return __('farmers.farmers');
    }

    public static function getModelLabel(): string
    {
        return __('farmers.farmer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('farmers.farmers');
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-user-group';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'phone', 'location'];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('general_manager') ?? false;
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label(__('farmers.name'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->minLength(2),

                TextInput::make('phone')
                    ->label(__('farmers.phone'))
                    ->tel()
                    ->maxLength(20)
                    ->regex('/^[\+]?[\d\s\-]{7,20}$/')
                    ->validationMessages([
                        'regex' => __('general.invalid_phone'),
                    ]),

                TextInput::make('location')
                    ->label(__('farmers.location'))
                    ->maxLength(255),

                TextInput::make('sort_order')
                    ->label(__('farmers.sort_order'))
                    ->numeric()
                    ->default(0),

                Toggle::make('is_active')
                    ->label(__('general.active'))
                    ->default(true),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label(__('farmers.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label(__('farmers.phone'))
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('location')
                    ->label(__('farmers.location'))
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('sort_order')
                    ->label(__('farmers.sort_order'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label(__('general.active'))
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
                    ->visible(fn (Farmer $record): bool => static::canEdit($record)),
                DeleteAction::make()
                    ->visible(fn (Farmer $record): bool => static::canDelete($record)),
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
                Section::make()
                    ->schema([
                        TextEntry::make('name')->label(__('farmers.name')),
                        TextEntry::make('phone')->label(__('farmers.phone'))->placeholder('—'),
                        TextEntry::make('location')->label(__('farmers.location'))->placeholder('—'),
                        TextEntry::make('sort_order')->label(__('farmers.sort_order')),
                        IconEntry::make('is_active')->label(__('general.active_status'))->boolean(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFarmers::route('/'),
            'create' => Pages\CreateFarmer::route('/create'),
            'view' => Pages\ViewFarmer::route('/{record}'),
            'edit' => Pages\EditFarmer::route('/{record}/edit'),
        ];
    }
}
