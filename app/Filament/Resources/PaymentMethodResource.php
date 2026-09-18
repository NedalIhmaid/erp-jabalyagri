<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentMethodResource\Pages;
use App\Models\PaymentMethod;
use Filament\Actions\BulkActionGroup;
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
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PaymentMethodResource extends Resource
{
    private const COLOR_OPTIONS = [
        'info'    => 'info',
        'primary' => 'primary',
        'gray'    => 'gray',
        'warning' => 'warning',
        'success' => 'success',
        'danger'  => 'danger',
    ];

    protected static ?string $model = PaymentMethod::class;

    protected static ?int $navigationSort = 5;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.admin');
    }

    public static function getNavigationLabel(): string
    {
        return __('payment_methods.payment_methods');
    }

    public static function getModelLabel(): string
    {
        return __('payment_methods.payment_method');
    }

    public static function getPluralModelLabel(): string
    {
        return __('payment_methods.payment_methods');
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-banknotes';
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
                TextInput::make('name_ar')
                    ->label(__('payment_methods.name_ar'))
                    ->required()
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        if (blank($get('key')) && filled($get('name_en'))) {
                            $set('key', Str::slug($get('name_en'), '_'));
                        }
                    }),

                TextInput::make('name_en')
                    ->label(__('payment_methods.name_en'))
                    ->required()
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        if (blank($get('key'))) {
                            $set('key', Str::slug((string) $state, '_'));
                        }
                    }),

                TextInput::make('key')
                    ->label(__('payment_methods.key'))
                    ->helperText(__('payment_methods.key_hint'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50)
                    ->dehydrateStateUsing(fn ($state) => Str::slug((string) $state, '_')),

                Select::make('color')
                    ->label(__('payment_methods.color'))
                    ->options(self::COLOR_OPTIONS)
                    ->default('gray'),

                TextInput::make('icon')
                    ->label(__('payment_methods.icon'))
                    ->helperText(__('payment_methods.icon_hint'))
                    ->maxLength(100),

                TextInput::make('sort_order')
                    ->label(__('payment_methods.sort_order'))
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
                TextColumn::make('name_ar')
                    ->label(__('payment_methods.name_ar'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name_en')
                    ->label(__('payment_methods.name_en'))
                    ->searchable(),

                TextColumn::make('key')
                    ->label(__('payment_methods.key'))
                    ->badge()
                    ->color(fn (PaymentMethod $record) => $record->color ?: 'gray'),

                TextColumn::make('sort_order')
                    ->label(__('payment_methods.sort_order'))
                    ->sortable(),

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
                    ->visible(fn (PaymentMethod $record): bool => static::canEdit($record)),
                DeleteAction::make()
                    ->visible(fn (PaymentMethod $record): bool => static::canDelete($record)),
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
                        TextEntry::make('name_ar')->label(__('payment_methods.name_ar')),
                        TextEntry::make('name_en')->label(__('payment_methods.name_en')),
                        TextEntry::make('key')->label(__('payment_methods.key'))->badge(),
                        TextEntry::make('color')->label(__('payment_methods.color'))->placeholder('—'),
                        TextEntry::make('icon')->label(__('payment_methods.icon'))->placeholder('—'),
                        TextEntry::make('sort_order')->label(__('payment_methods.sort_order')),
                        IconEntry::make('is_active')->label(__('general.active_status'))->boolean(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'view' => Pages\ViewPaymentMethod::route('/{record}'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}
