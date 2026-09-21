<?php

namespace App\Filament\Resources;

use App\Enums\ApprovalAction;
use App\Enums\ProjectType;
use App\Enums\SalesRequestStatus;
use App\Filament\Resources\SalesApprovalRequestResource\Pages;
use App\Models\CompanyMaterial;
use App\Models\CompanyMaterialCategory;
use App\Models\PaymentMethod;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SalesApprovalService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

class SalesApprovalRequestResource extends Resource
{
    protected static ?string $model = SalesApprovalRequest::class;

    protected static ?string $recordTitleAttribute = 'request_number';

    protected static ?int $navigationSort = 1;

    public static function getGloballySearchableAttributes(): array
    {
        return ['request_number', 'client_name', 'client_phone', 'client_address', 'region'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->request_number.' — '.$record->client_name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('sales.client_phone') => $record->client_phone ?? '—',
            __('general.status') => $record->status?->getLabel() ?? '—',
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.sales_requests');
    }

    public static function getNavigationSort(): int
    {
        return 1;
    }

    public static function getModelLabel(): string
    {
        return __('navigation.sales_requests');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.sales_requests');
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-check';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                // Client Information
                Section::make(__('sales.client_name'))
                    ->schema([
                        TextInput::make('client_name')
                            ->label(__('sales.client_name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('client_phone')
                            ->label(__('sales.client_phone'))
                            ->tel()
                            ->maxLength(20),

                        Textarea::make('client_address')
                            ->label(__('sales.client_address'))
                            ->rows(2),

                        TextInput::make('region')
                            ->label(__('sales.region'))
                            ->maxLength(100),

                        Select::make('project_type')
                            ->label(__('sales.project_type'))
                            ->options(fn () => collect(ProjectType::cases())
                                ->mapWithKeys(fn ($t) => [$t->value => $t->getLabel()])),

                        TextInput::make('project_size')
                            ->label(__('sales.project_size'))
                            ->placeholder(__('sales.project_size_placeholder'))
                            ->maxLength(50),

                        Select::make('payment_method')
                            ->label(__('general.payment_method'))
                            ->options(fn () => PaymentMethod::options())
                            ->required(),

                        Select::make('warehouse_keeper_id')
                            ->label(__('sales.warehouse_keeper'))
                            ->helperText(__('sales.warehouse_keeper_help'))
                            ->options(fn (): array => User::role('warehouse_keeper')
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(2),

                // Company materials
                Section::make(__('sales.items'))
                    ->schema([
                        Repeater::make('items')
                            ->label(__('sales.items'))
                            ->hiddenLabel()
                            ->relationship()
                            ->schema([
                                Select::make('company_material_id')
                                    ->label(__('company.material'))
                                    ->options(fn (): array => CompanyMaterial::query()
                                        ->where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->all())
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('name')->label(__('company.name'))->required()->maxLength(255),
                                        Select::make('company_material_category_id')
                                            ->label(__('company.category'))
                                            ->options(fn (): array => CompanyMaterialCategory::query()->orderBy('sort')->pluck('name', 'id')->all())
                                            ->searchable()->required(),
                                        TextInput::make('unit')->label(__('company.unit'))->required()->maxLength(50),
                                    ])
                                    ->createOptionUsing(fn (array $data): int => CompanyMaterial::create($data + ['is_active' => true])->id)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $material = filled($state) ? CompanyMaterial::find($state) : null;

                                        $set('product_unit_id', null);
                                        $set('product_id', null);
                                        $set('unit', $material?->unit);
                                        $set('unit_price', null);
                                        $set('total_price', 0);
                                        $set('product_name', $material?->name);
                                    })
                                    ->columnSpanFull(),

                                TextInput::make('product_name')
                                    ->label(__('company.material'))
                                    ->required()
                                    ->disabled()
                                    ->dehydrated()
                                    ->maxLength(255),

                                TextInput::make('quantity')
                                    ->label(__('sales.quantity'))
                                    ->numeric()
                                    ->required()
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $quantity = floatval($state);
                                        if ($quantity <= 0) {
                                            $set('total_price', 0);

                                            return;
                                        }
                                        $set('total_price', round($quantity * floatval($get('unit_price')), 2));
                                    }),

                                TextInput::make('unit')
                                    ->label(__('sales.unit'))
                                    ->required()
                                    ->maxLength(50),

                                TextInput::make('unit_price')
                                    ->label(__('sales.unit_price'))
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->prefix('JOD')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $price = floatval($state);
                                        if ($price <= 0) {
                                            $set('total_price', 0);

                                            return;
                                        }
                                        $set('total_price', round(floatval($get('quantity')) * $price, 2));
                                    }),

                                TextInput::make('total_price')
                                    ->label(__('sales.total_price'))
                                    ->numeric()
                                    ->prefix('JOD')
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(0),
                            ])
                            ->columns(4)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $total = collect($state)->sum('total_price');
                                $set('total_amount', round(floatval($total), 2));
                            }),
                    ]),

                // Notes
                Section::make(__('general.notes'))
                    ->schema([
                        Textarea::make('engineer_notes')
                            ->label(__('sales.engineer_notes'))
                            ->rows(3),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Section::make(__('sales.request_details'))
                    ->schema([
                        TextEntry::make('request_number')->label(__('sales.request_number')),
                        TextEntry::make('user.name')->label(__('general.engineer')),
                        TextEntry::make('warehouseKeeper.name')->label(__('sales.warehouse_keeper')),
                        TextEntry::make('client_name')->label(__('sales.client_name')),
                        TextEntry::make('client_phone')->label(__('sales.client_phone')),
                        TextEntry::make('client_address')->label(__('sales.client_address')),
                        TextEntry::make('region')->label(__('sales.region'))->placeholder('—'),
                        TextEntry::make('project_type')->label(__('sales.project_type'))->badge()->placeholder('—'),
                        TextEntry::make('project_size')->label(__('sales.project_size'))->placeholder('—'),
                        TextEntry::make('payment_method')->label(__('general.payment_method'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => PaymentMethod::labelFor($state))
                            ->color(fn ($state) => PaymentMethod::colorFor($state)),
                        TextEntry::make('total_amount')->label(__('sales.total_amount'))
                            ->money('JOD'),
                        TextEntry::make('status')->label(__('general.status'))
                            ->badge(),
                        TextEntry::make('current_stage')->label(__('sales.current_stage'))
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => __('sales.stage')." {$state}/3"),
                        TextEntry::make('engineer_notes')->label(__('sales.engineer_notes'))
                            ->columnSpanFull(),
                        TextEntry::make('rejection_reason')->label(__('general.reason'))
                            ->columnSpanFull()
                            ->hidden(fn ($record) => blank($record?->rejection_reason)),
                    ])->columns(3),

                Section::make(__('sales.items'))
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label(__('sales.items'))
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('display_product_name')->label(__('company.material')),
                                TextEntry::make('quantity')->label(__('sales.quantity'))->numeric(),
                                TextEntry::make('unit')->label(__('sales.unit')),
                                TextEntry::make('unit_price')->label(__('sales.unit_price'))->money('JOD'),
                                TextEntry::make('total_price')->label(__('sales.total_price'))->money('JOD'),
                            ])
                            ->columns(6)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('sales.approval_stages'))
                    ->schema([
                        RepeatableEntry::make('approvalStages')
                            ->label(__('sales.approval_stages'))
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('stage_number')
                                    ->label(__('sales.stage'))
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => __('sales.stage')." {$state}"),
                                TextEntry::make('role')
                                    ->label(__('general.role'))
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => filled($state)
                                        ? (trans()->has("roles.{$state}") ? __("roles.{$state}") : str($state)->replace('_', ' ')->title()->toString())
                                        : '—'),
                                TextEntry::make('approver.name')->label(__('general.approver'))
                                    ->placeholder('—'),
                                TextEntry::make('action')
                                    ->label(__('general.action'))
                                    ->badge()
                                    ->formatStateUsing(fn (?ApprovalAction $state): ?string => $state?->getLabel())
                                    ->placeholder('—'),
                                TextEntry::make('comments')->label(__('general.comments'))
                                    ->placeholder('—'),
                                TextEntry::make('acted_at')->label(__('general.date_label'))
                                    ->dateTime()
                                    ->placeholder('—'),
                            ])
                            ->columns(6)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('request_number')
                    ->label(__('sales.request_number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label(__('general.engineer'))
                    ->searchable(),

                TextColumn::make('warehouseKeeper.name')
                    ->label(__('sales.warehouse_keeper'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('client_name')
                    ->label(__('sales.client_name'))
                    ->searchable(),

                TextColumn::make('total_amount')
                    ->label(__('sales.total_amount'))
                    ->money('JOD')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('general.status'))
                    ->badge()
                    ->color(fn ($state) => $state?->getColor())
                    ->formatStateUsing(fn ($state) => $state?->getLabel())
                    ->sortable(),

                TextColumn::make('current_stage')
                    ->label(__('sales.current_stage'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('sales.stage')." {$state}/3")
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('general.date'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('general.status'))
                    ->options(fn () => collect(SalesRequestStatus::cases())
                        ->mapWithKeys(fn ($s) => [$s->value => $s->getLabel()])),

                SelectFilter::make('current_stage')
                    ->label(__('sales.stage'))
                    ->options([
                        1 => __('general.stage_1_warehouse'),
                        2 => __('general.stage_2_financial'),
                        3 => __('general.stage_3_purchasing'),
                    ]),
            ])
            ->actions(static::getActions())
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->using(function (DeleteBulkAction $action, EloquentCollection|Collection|LazyCollection $records): void {
                            $records->each(function (SalesApprovalRequest $record) use ($action): void {
                                $before = app(AuditLogger::class)->salesRequestState($record->fresh(['user', 'items']));

                                if (! $record->delete()) {
                                    $action->reportBulkProcessingFailure();

                                    return;
                                }

                                app(AuditLogger::class)->logSalesRequestDeleted($record, auth()->user(), $before);
                            });
                        }),
                ]),
            ]);
    }

    public static function userCanAccessRequestPdf(SalesApprovalRequest $record): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $record->status === SalesRequestStatus::Approved
            || $record->user_id === $user->id
            || $user->hasRole(['sales_manager', 'purchasing_manager', 'financial_manager', 'general_manager', 'warehouse_keeper']);
    }

    /** @return array<int, Action> */
    public static function getRecordWorkflowActions(): array
    {
        return [
            Action::make('approve')
                ->label(__('general.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(__('general.approve'))
                ->modalDescription(__('general.review_before_confirming'))
                ->modalContent(fn (SalesApprovalRequest $record) => view('filament.resources.sales-approval-requests.partials.action-confirmation-details', [
                    'record' => $record,
                ]))
                ->modalSubmitActionLabel(__('general.confirm'))
                ->modalCancelActionLabel(__('general.cancel'))
                ->visible(fn (SalesApprovalRequest $record) => app(SalesApprovalService::class)->canActOnRequest($record, auth()->user()))
                ->form([
                    Textarea::make('comments')->label(__('general.comments')),
                ])
                ->action(function (SalesApprovalRequest $record, array $data): void {
                    app(SalesApprovalService::class)->approve($record, auth()->user(), $data['comments'] ?? null);

                    Notification::make()
                        ->title(__('general.approved'))
                        ->success()
                        ->send();
                }),

            Action::make('reject')
                ->label(__('general.reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('general.reject'))
                ->modalDescription(__('general.review_before_confirming'))
                ->modalContent(fn (SalesApprovalRequest $record) => view('filament.resources.sales-approval-requests.partials.action-confirmation-details', [
                    'record' => $record,
                ]))
                ->modalSubmitActionLabel(__('general.confirm'))
                ->modalCancelActionLabel(__('general.cancel'))
                ->visible(fn (SalesApprovalRequest $record) => app(SalesApprovalService::class)->canActOnRequest($record, auth()->user()))
                ->form([
                    Textarea::make('rejection_reason')->label(__('general.reason'))->required(),
                ])
                ->action(function (SalesApprovalRequest $record, array $data): void {
                    app(SalesApprovalService::class)->reject($record, auth()->user(), $data['rejection_reason']);

                    Notification::make()
                        ->title(__('general.rejected'))
                        ->danger()
                        ->send();
                }),

            Action::make('return')
                ->label(__('general.return'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('general.return'))
                ->modalDescription(__('general.review_before_confirming'))
                ->modalContent(fn (SalesApprovalRequest $record) => view('filament.resources.sales-approval-requests.partials.action-confirmation-details', [
                    'record' => $record,
                ]))
                ->modalSubmitActionLabel(__('general.confirm'))
                ->modalCancelActionLabel(__('general.cancel'))
                ->visible(fn (SalesApprovalRequest $record) => app(SalesApprovalService::class)->canReturnRequest($record, auth()->user()))
                ->form([
                    Textarea::make('return_reason')->label(__('general.reason'))->required(),
                ])
                ->action(function (SalesApprovalRequest $record, array $data): void {
                    app(SalesApprovalService::class)->returnToEngineer($record, auth()->user(), $data['return_reason']);

                    Notification::make()
                        ->title(__('general.returned'))
                        ->warning()
                        ->send();
                }),
        ];
    }

    protected static function getActions(): array
    {
        return [
            ViewAction::make(),

            Action::make('print_pdf')
                ->label(__('sales.print_pdf'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (SalesApprovalRequest $record) => route('sales-requests.pdf', $record))
                ->openUrlInNewTab()
                ->visible(fn (SalesApprovalRequest $record) => static::userCanAccessRequestPdf($record)),

            ...static::getRecordWorkflowActions(),

            EditAction::make()
                ->visible(fn (SalesApprovalRequest $record) => auth()->user()
                    && app(SalesApprovalService::class)->engineerCanMutateOwnRequest($record, auth()->user())),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesApprovalRequests::route('/'),
            'create' => Pages\CreateSalesApprovalRequest::route('/create'),
            'view' => Pages\ViewSalesApprovalRequest::route('/{record}'),
            'edit' => Pages\EditSalesApprovalRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return parent::getEloquentQuery();
        }

        // Engineers who are also approvers need the complete approval queue.
        if ($user->hasRole('engineer') && ! $user->hasSalesApprovalRole()) {
            return parent::getEloquentQuery()->where('user_id', $user->id);
        }

        // All other roles see all requests
        return parent::getEloquentQuery();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('engineer') ?? false;
    }
}
