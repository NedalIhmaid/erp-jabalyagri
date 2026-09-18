<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Services\AdminSafetyService;
use App\Services\AuditLogger;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\LazyCollection;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('general.email') => $record->email ?? '—',
            __('general.role') => $record->roles->first()?->name ?? '—',
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.admin');
    }

    public static function getNavigationLabel(): string
    {
        return __('general.users');
    }

    public static function getNavigationSort(): int
    {
        return 1;
    }

    public static function getModelLabel(): string
    {
        return __('general.users');
    }

    public static function getPluralModelLabel(): string
    {
        return __('general.users');
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-users';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label(__('general.name'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label(__('general.email'))
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('password')
                    ->label(__('general.password'))
                    ->password()
                    ->revealable()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->maxLength(255),

                TextInput::make('phone')
                    ->label(__('general.phone'))
                    ->tel()
                    ->maxLength(20),

                Select::make('manager_id')
                    ->label(__('general.direct_manager'))
                    ->relationship('manager', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),

                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn ($record) => __('roles.'.$record->name))
                    ->required(),

                Select::make('locale')
                    ->label(__('general.language'))
                    ->options([
                        'ar' => 'العربية',
                        'en' => 'English',
                    ])
                    ->default('ar')
                    ->required(),

                Toggle::make('is_active')
                    ->label(__('general.active'))
                    ->default(true),

                DatePicker::make('hire_date')
                    ->label(__('general.hire_date'))
                    ->native(false)
                    ->default(now())
                    ->required(),

                Select::make('gender')
                    ->label(__('general.gender'))
                    ->options([
                        \App\Enums\Gender::Male->value => __('general.gender_male'),
                        \App\Enums\Gender::Female->value => __('general.gender_female'),
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('general.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('general.email'))
                    ->searchable(),

                TextColumn::make('phone')
                    ->label(__('general.phone'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('roles.name')
                    ->label(__('general.role'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('roles.'.$state))
                    ->sortable(),

                TextColumn::make('manager.name')
                    ->label(__('general.direct_manager'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label(__('general.active'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('hire_date')
                    ->label(__('general.hire_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label(__('general.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('general.active_status')),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (User $record): bool => app(AdminSafetyService::class)->shouldHideUserDelete($record, auth()->user()))
                    ->using(function (User $record): bool {
                        app(AdminSafetyService::class)->ensureUserDeletionAllowed($record, auth()->user());

                        $before = app(AuditLogger::class)->userState($record->fresh('roles'));
                        $result = $record->delete();

                        if ($result) {
                            app(AuditLogger::class)->logUserDeleted($record, auth()->user(), $before);
                        }

                        return $result;
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->using(function (DeleteBulkAction $action, EloquentCollection|Collection|LazyCollection $records): void {
                            $records->each(function (User $record) use ($action): void {
                                try {
                                    app(AdminSafetyService::class)->ensureUserDeletionAllowed($record, auth()->user());

                                    $before = app(AuditLogger::class)->userState($record->fresh('roles'));

                                    if (! $record->delete()) {
                                        $action->reportBulkProcessingFailure();

                                        return;
                                    }

                                    app(AuditLogger::class)->logUserDeleted($record, auth()->user(), $before);
                                } catch (Halt) {
                                    $action->reportBulkProcessingFailure();
                                }
                            });
                        }),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make()
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('general.name')),

                        TextEntry::make('email')
                            ->label(__('general.email')),

                        TextEntry::make('phone')
                            ->label(__('general.phone'))
                            ->placeholder('—'),

                        TextEntry::make('roles.name')
                            ->label(__('general.role'))
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => __('roles.'.$state)),

                        TextEntry::make('manager.name')
                            ->label(__('general.direct_manager'))
                            ->placeholder('—'),

                        TextEntry::make('locale')
                            ->label(__('general.language'))
                            ->formatStateUsing(fn (string $state): string => $state === 'ar' ? 'العربية' : 'English'),

                        IconEntry::make('is_active')
                            ->label(__('general.active'))
                            ->boolean(),

                        TextEntry::make('hire_date')
                            ->label(__('general.hire_date'))
                            ->date()
                            ->placeholder('—'),

                        TextEntry::make('created_at')
                            ->label(__('general.created'))
                            ->dateTime(),

                        TextEntry::make('updated_at')
                            ->label(__('general.updated'))
                            ->dateTime(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('general_manager') ?? false;
    }
}
