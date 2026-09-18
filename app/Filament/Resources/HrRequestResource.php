<?php

namespace App\Filament\Resources;

use App\Enums\HrRequestStatus;
use App\Enums\HrRequestType;
use App\Filament\Resources\HrRequestResource\Pages;
use App\Models\HrRequest;
use App\Services\AuditLogger;
use App\Services\HrRequestService;
use App\Services\LeaveService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

class HrRequestResource extends Resource
{
    protected static ?string $model = HrRequest::class;

    protected static ?int $navigationSort = 1;

    public static function calculateDurationDays(mixed $startDate, mixed $endDate): ?int
    {
        if (! $startDate || ! $endDate) {
            return null;
        }

        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($end->lessThan($start)) {
            return null;
        }

        return $start->diffInDays($end) + 1;
    }

    public static function setCalculatedDurationDays(callable $set, callable $get): void
    {
        $set('duration_days', static::calculateDurationDays($get('start_date'), $get('end_date')));
    }

    public static function getRecordTitle(?Model $record): Htmlable|string|null
    {
        if (! $record) {
            return null;
        }

        return $record->type?->getLabel() ?? (string) $record->type;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['type', 'notes'];
    }

    public static function getGlobalSearchResultTitle(Model $record): Htmlable|string
    {
        return $record->type?->getLabel() ?? (string) $record->type;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('general.employee') => $record->user?->name ?? '—',
            __('general.status') => $record->status?->getLabel() ?? '—',
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return auth()->user()?->hasRole('general_manager')
            ? __('navigation.admin')
            : null;
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.hr_requests');
    }

    public static function getNavigationSort(): int
    {
        return 99;
    }

    public static function getModelLabel(): string
    {
        return __('navigation.hr_requests');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.hr_requests');
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-text';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('type')
                    ->label(__('general.request_type'))
                    ->options(function () {
                        $gender = auth()->user()?->gender?->value;

                        return collect(HrRequestType::cases())
                            ->reject(function ($t) use ($gender) {
                                if ($t === HrRequestType::MaternityLeave && $gender !== 'female') {
                                    return true;
                                }
                                if ($t === HrRequestType::PaternityLeave && $gender !== 'male') {
                                    return true;
                                }

                                return false;
                            })
                            ->mapWithKeys(fn ($t) => [$t->value => $t->getLabel()]);
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $isDeparture = in_array($state, [
                            HrRequestType::EarlyDeparture->value,
                            HrRequestType::DepartureFromAnnual->value,
                        ]);
                        $set('_is_departure', $isDeparture);
                        $isDeparture
                            ? $set('duration_days', null)
                            : static::setCalculatedDurationDays($set, $get);
                    }),

                DatePicker::make('start_date')
                    ->label(__('hr.start_date'))
                    ->required()
                    ->default(now())
                    ->minDate(now())
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $endDate = $get('end_date');

                        if ($state && $endDate && Carbon::parse($endDate)->lessThan(Carbon::parse($state))) {
                            $set('end_date', $state);
                            $set('duration_days', static::calculateDurationDays($state, $state));

                            return;
                        }

                        static::setCalculatedDurationDays($set, $get);
                    }),

                DatePicker::make('end_date')
                    ->label(__('hr.end_date'))
                    ->visible(fn (callable $get) => ! $get('_is_departure'))
                    ->required(fn (callable $get) => ! $get('_is_departure'))
                    ->minDate(fn (callable $get) => $get('start_date') ?? now())
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $startDate = $get('start_date');

                        if ($startDate && $state && Carbon::parse($state)->lessThan(Carbon::parse($startDate))) {
                            $set('end_date', $startDate);
                            $set('duration_days', static::calculateDurationDays($startDate, $startDate));

                            return;
                        }

                        static::setCalculatedDurationDays($set, $get);
                    }),

                TimePicker::make('start_time')
                    ->label(__('hr.start_time'))
                    ->visible(fn (callable $get) => $get('_is_departure'))
                    ->required(fn (callable $get) => $get('_is_departure'))
                    ->seconds(false),

                TimePicker::make('end_time')
                    ->label(__('hr.end_time'))
                    ->visible(fn (callable $get) => $get('_is_departure'))
                    ->required(fn (callable $get) => $get('_is_departure'))
                    ->seconds(false)
                    ->after(fn (callable $get) => $get('start_time'))
                    ->rule(fn (callable $get) => function ($attribute, $value, $fail) use ($get) {
                        $startTime = $get('start_time');
                        if ($startTime && $value && $value <= $startTime) {
                            $fail(__('hr.end_time must be after start time.'));
                        }
                    }),

                TextInput::make('duration_days')
                    ->label(__('hr.duration_days'))
                    ->numeric()
                    ->required(fn (callable $get) => ! $get('_is_departure'))
                    ->minValue(1)
                    ->disabled()
                    ->dehydrated()
                    ->visible(fn (callable $get) => ! $get('_is_departure')),

                Placeholder::make('leave_balance_hint')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->visible(function (callable $get): bool {
                        $type = $get('type');
                        $user = auth()->user();

                        return $type && $user
                            && app(LeaveService::class)->remainingFor($user, HrRequestType::from($type)) !== null;
                    })
                    ->content(function (callable $get): Htmlable {
                        $service = app(LeaveService::class);
                        $user = auth()->user();
                        $type = HrRequestType::from($get('type'));

                        $days = (int) ($get('duration_days') ?? 0);
                        $start = $get('start_date') ? Carbon::parse($get('start_date')) : now();

                        $current = $service->remainingFor($user, $type);
                        $until = $service->remainingFor($user, $type, $start);

                        $fmt = fn (?float $n): string => rtrim(rtrim(number_format((float) $n, 3, '.', ''), '0'), '.');

                        $lines = [
                            e(__('hr.requested_days', ['days' => $days])),
                            e(__('hr.current_balance', ['days' => $fmt($current)])),
                            e(__('hr.balance_until_leave', ['days' => $fmt($until)])),
                        ];

                        $html = '<div class="text-sm leading-6">'
                            .implode('', array_map(fn ($l) => "<div>{$l}</div>", $lines));

                        if ($days > 0 && $until !== null && $until < $days) {
                            $html .= '<div class="mt-1 font-semibold text-danger-600">'.e(__('leave.insufficient')).'</div>';
                        }

                        $html .= '</div>';

                        return new HtmlString($html);
                    }),

                Textarea::make('reason')
                    ->label(__('hr.reason'))
                    ->rows(3)
                    ->maxLength(1000),

                FileUpload::make('attachment')
                    ->label(__('hr.attachment'))
                    ->directory('hr-attachments')
                    ->visibility('private')
                    ->maxSize(5120)
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('general.employee'))
                    ->searchable(),

                TextColumn::make('type')
                    ->label(__('general.type'))
                    ->badge()
                    ->color(fn ($state) => $state?->getColor())
                    ->formatStateUsing(fn ($state) => $state?->getLabel())
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label(__('hr.start_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label(__('hr.end_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label(__('general.status'))
                    ->badge()
                    ->color(fn ($state) => $state?->getColor())
                    ->formatStateUsing(fn ($state) => $state?->getLabel())
                    ->sortable(),

                TextColumn::make('manager.name')
                    ->label(__('hr.direct_manager'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('general.submitted'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('general.status'))
                    ->options(fn () => collect(HrRequestStatus::cases())
                        ->mapWithKeys(fn ($s) => [$s->value => $s->getLabel()])),

                SelectFilter::make('type')
                    ->label(__('general.type'))
                    ->options(fn () => collect(HrRequestType::cases())
                        ->mapWithKeys(fn ($t) => [$t->value => $t->getLabel()])),
            ])
            ->actions(static::getActions())
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->using(function (DeleteBulkAction $action, EloquentCollection|Collection|LazyCollection $records): void {
                            $records->each(function (HrRequest $record) use ($action): void {
                                $before = app(AuditLogger::class)->hrRequestState($record->fresh(['user', 'manager']));

                                if (! $record->delete()) {
                                    $action->reportBulkProcessingFailure();

                                    return;
                                }

                                app(AuditLogger::class)->logHrRequestDeleted($record, auth()->user(), $before);
                            });
                        }),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('general.request_summary'))
                    ->schema([
                        TextEntry::make('user.name')
                            ->label(__('general.employee')),
                        TextEntry::make('type')
                            ->label(__('general.type'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state?->getLabel()),
                        TextEntry::make('status')
                            ->label(__('general.status'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state?->getLabel()),
                        TextEntry::make('manager.name')
                            ->label(__('hr.direct_manager'))
                            ->placeholder('—'),
                        TextEntry::make('start_date')
                            ->label(__('hr.start_date'))
                            ->date(),
                        TextEntry::make('end_date')
                            ->label(__('hr.end_date'))
                            ->date()
                            ->placeholder('—'),
                        TextEntry::make('start_time')
                            ->label(__('hr.start_time'))
                            ->placeholder('—'),
                        TextEntry::make('end_time')
                            ->label(__('hr.end_time'))
                            ->placeholder('—'),
                        TextEntry::make('duration_days')
                            ->label(__('hr.duration_days'))
                            ->placeholder('—'),
                        TextEntry::make('reason')
                            ->label(__('hr.reason'))
                            ->columnSpanFull()
                            ->placeholder('—'),
                        TextEntry::make('manager_comments')
                            ->label(__('general.manager_comments'))
                            ->columnSpanFull()
                            ->placeholder('—'),
                        TextEntry::make('gm_comments')
                            ->label(__('general.gm_comments'))
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ])
                    ->columns(3),
            ]);
    }

    protected static function getActions(): array
    {
        $actions = [
            ViewAction::make(),
        ];

        // Manager approval/rejection (Stage 1)
        if (auth()->user()) {
            $service = app(HrRequestService::class);

            $actions[] = Action::make('approve_as_manager')
                ->label(__('general.approve_manager'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(function ($record) use ($service) {
                    return auth()->user() && $service->canApproveAsManager($record, auth()->user());
                })
                ->form([
                    Textarea::make('comments')->label(__('general.comments')),
                ])
                ->action(function (HrRequest $record, array $data): void {
                    $service = app(HrRequestService::class);
                    $service->approveAsManager($record, auth()->user(), $data['comments'] ?? null);

                    Notification::make()
                        ->title(__('general.request_approved_manager'))
                        ->success()
                        ->send();
                });

            $actions[] = Action::make('reject_as_manager')
                ->label(__('general.reject_manager'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(function ($record) use ($service) {
                    return auth()->user() && $service->canApproveAsManager($record, auth()->user());
                })
                ->form([
                    Textarea::make('comments')->label(__('general.comments'))->required(),
                ])
                ->action(function (HrRequest $record, array $data): void {
                    $service = app(HrRequestService::class);
                    $service->rejectAsManager($record, auth()->user(), $data['comments']);

                    Notification::make()
                        ->title(__('general.request_rejected_manager'))
                        ->danger()
                        ->send();
                });

            // GM approval/rejection (Stage 2)
            $actions[] = Action::make('approve_as_gm')
                ->label(__('general.approve_gm'))
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->visible(function ($record) use ($service) {
                    return auth()->user() && $service->canApproveAsGM($record, auth()->user());
                })
                ->form([
                    Textarea::make('comments')->label(__('general.comments')),
                ])
                ->action(function (HrRequest $record, array $data): void {
                    $service = app(HrRequestService::class);
                    $service->approveAsGM($record, auth()->user(), $data['comments'] ?? null);

                    Notification::make()
                        ->title(__('general.request_approved_gm'))
                        ->success()
                        ->send();
                });

            $actions[] = Action::make('reject_as_gm')
                ->label(__('general.reject_gm'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(function ($record) use ($service) {
                    return auth()->user() && $service->canApproveAsGM($record, auth()->user());
                })
                ->form([
                    Textarea::make('comments')->label(__('general.comments'))->required(),
                ])
                ->action(function (HrRequest $record, array $data): void {
                    $service = app(HrRequestService::class);
                    $service->rejectAsGM($record, auth()->user(), $data['comments']);

                    Notification::make()
                        ->title(__('general.request_rejected_gm'))
                        ->danger()
                        ->send();
                });
        }

        // Edit for employee (pending only)
        $actions[] = EditAction::make()
            ->visible(fn (HrRequest $record) => auth()->user()?->id === $record->user_id && $record->status === HrRequestStatus::Pending);

        return $actions;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHrRequests::route('/'),
            'create' => Pages\CreateHrRequest::route('/create'),
            'view' => Pages\ViewHrRequest::route('/{record}'),
            'edit' => Pages\EditHrRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return parent::getEloquentQuery();
        }

        // General manager sees all
        if ($user->hasRole('general_manager')) {
            return parent::getEloquentQuery();
        }

        $directReportIds = $user->directReports()->pluck('id');

        return parent::getEloquentQuery()
            ->where(function (Builder $q) use ($user, $directReportIds) {
                $q->where('user_id', $user->id)
                    ->orWhereIn('user_id', $directReportIds);
            });
    }

    public static function canCreate(): bool
    {
        return ! auth()->user()?->hasRole('general_manager') ?? false;
    }
}
