<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyVisitResource\Pages;
use App\Models\DailyVisit;
use App\Models\Farmer;
use App\Services\Images\ImageSanitizer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DailyVisitResource extends Resource
{
    protected static ?string $model = DailyVisit::class;

    protected static ?string $recordTitleAttribute = 'client_name';

    protected static ?int $navigationSort = 1;

    public static function getGloballySearchableAttributes(): array
    {
        return ['client_name', 'client_phone', 'location_text', 'visit_reason'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            __('general.date')  => $record->visit_date?->format('Y-m-d') ?? '—',
            __('general.farmer_phone') => $record->client_phone ?? '—',
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    public static function getNavigationLabel(): string
    {
        return __('general.visits');
    }

    public static function getNavigationSort(): int
    {
        return 2;
    }

    public static function getModelLabel(): string
    {
        return __('general.visits');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.visits');
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-map-pin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                DatePicker::make('visit_date')
                    ->label(__('general.date'))
                    ->required()
                    ->default(now())
                    ->maxDate(now())
                    ->native(false),

                Select::make('farmer_id')
                    ->label(__('general.farmer_name'))
                    ->relationship('farmer', 'name', fn (Builder $query) => $query->where('is_active', true))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set): void {
                        $farmer = Farmer::find($state);

                        if ($farmer) {
                            $set('client_name', $farmer->name);
                            $set('client_phone', $farmer->phone);
                            $set('location_text', $farmer->location);
                        }
                    })
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label(__('general.farmer_name'))
                            ->required()
                            ->unique('farmers', 'name')
                            ->maxLength(255)
                            ->minLength(2),
                        TextInput::make('phone')
                            ->label(__('general.farmer_phone'))
                            ->tel()
                            ->maxLength(20)
                            ->regex('/^[\+]?[\d\s\-]{7,20}$/')
                            ->validationMessages([
                                'regex' => __('general.invalid_phone'),
                            ]),
                        TextInput::make('location')
                            ->label(__('general.location'))
                            ->maxLength(255),
                    ])
                    ->createOptionUsing(fn (array $data): int => Farmer::create($data + ['is_active' => true])->id),

                TextInput::make('client_phone')
                    ->label(__('general.farmer_phone'))
                    ->tel()
                    ->maxLength(20)
                    ->regex('/^[\+]?[\d\s\-]{7,20}$/')
                    ->validationMessages([
                        'regex' => __('general.invalid_phone'),
                    ]),

                TextInput::make('location_text')
                    ->label(__('general.location'))
                    ->maxLength(255),

                Textarea::make('visit_reason')
                    ->label(__('general.visit_reason'))
                    ->required()
                    ->rows(3)
                    ->minLength(10),

                Textarea::make('visit_results')
                    ->label(__('general.visit_results'))
                    ->rows(3)
                    ->maxLength(1000),

                FileUpload::make('visit_photo')
                    ->label(__('general.photo'))
                    ->helperText(__('general.tap_to_capture'))
                    ->image()
                    ->imageEditor()
                    ->directory('visit-photos')
                    ->visibility('private')
                    ->maxSize(8192)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->saveUploadedFileUsing(function ($file, $component) {
                        $disk = method_exists($component, 'getDiskName') ? $component->getDiskName() : config('filesystems.default');

                        return app(ImageSanitizer::class)->storeSanitized($file, 'visit-photos', $disk);
                    }),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make()
                    ->schema([
                        TextEntry::make('visit_date')->label(__('general.date'))->date(),
                        TextEntry::make('user.name')->label(__('general.engineer')),
                        TextEntry::make('client_name')->label(__('general.farmer_name')),
                        TextEntry::make('client_phone')->label(__('general.farmer_phone')),
                        TextEntry::make('location_text')->label(__('general.location')),
                        TextEntry::make('visit_reason')->label(__('general.visit_reason'))->columnSpanFull(),
                        TextEntry::make('visit_results')->label(__('general.visit_results'))->columnSpanFull(),
                    ])->columns(3),

                View::make('filament.infolist.map-entry')
                    ->columnSpanFull()
                    ->visible(fn (DailyVisit $record): bool => filled($record->latitude) && filled($record->longitude)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('visit_date')
                    ->label(__('general.date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label(__('general.engineer'))
                    ->searchable(),

                TextColumn::make('client_name')
                    ->label(__('general.farmer_name'))
                    ->searchable(),

                TextColumn::make('client_phone')
                    ->label(__('general.farmer_phone'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('location_text')
                    ->label(__('general.location'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('general.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('visit_date')
                    ->form([
                        DatePicker::make('from')->label(__('general.from')),
                        DatePicker::make('until')->label(__('general.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->where('visit_date', '>=', $d))
                            ->when($data['until'] ?? null, fn ($q, $d) => $q->where('visit_date', '<=', $d));
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (DailyVisit $record) => auth()->user()->hasRole('engineer') && $record->user_id === auth()->id()),
                DeleteAction::make()
                    ->visible(fn (DailyVisit $record) => auth()->user()->hasRole('engineer') && $record->user_id === auth()->id()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasRole('engineer')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyVisits::route('/'),
            'create' => Pages\CreateDailyVisit::route('/create'),
            'view' => Pages\ViewDailyVisit::route('/{record}'),
            'edit' => Pages\EditDailyVisit::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return parent::getEloquentQuery();
        }

        if ($user->hasRole('engineer')) {
            return parent::getEloquentQuery()->where('user_id', $user->id);
        }

        if ($user->hasRole('general_manager')) {
            return parent::getEloquentQuery();
        }

        if ($user->hasRole('sales_manager')) {
            return parent::getEloquentQuery()
                ->whereHas('user', fn ($q) => $q->where('manager_id', $user->id));
        }

        // Other roles can't see visits
        return parent::getEloquentQuery()->whereRaw('0 = 1');
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('engineer') ?? false;
    }
}
