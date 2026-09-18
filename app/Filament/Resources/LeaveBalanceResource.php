<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveBalanceResource\Pages;
use App\Models\LeaveBalance;
use App\Services\LeaveService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeaveBalanceResource extends Resource
{
    protected static ?string $model = LeaveBalance::class;

    protected static ?int $navigationSort = 2;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.leave_balances');
    }

    public static function getModelLabel(): string
    {
        return __('navigation.leave_balance');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.leave_balances');
    }

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('sales_manager') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('sales_manager') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('leave.annual'))
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('annual_total')
                            ->label(__('leave.annual_total'))
                            ->numeric()
                            ->step(0.5)
                            ->minValue(0)
                            ->maxValue(365)
                            ->required(),

                        TextInput::make('annual_used')
                            ->label(__('leave.annual_used'))
                            ->numeric()
                            ->step(0.5)
                            ->minValue(0)
                            ->required(),
                    ]),
                ]),

            Section::make(__('leave.sick'))
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('sick_total')
                            ->label(__('leave.sick_total'))
                            ->numeric()
                            ->step(0.5)
                            ->minValue(0)
                            ->required(),

                        TextInput::make('sick_used')
                            ->label(__('leave.sick_used'))
                            ->numeric()
                            ->step(0.5)
                            ->minValue(0)
                            ->required(),
                    ]),
                ]),

            Section::make(__('leave.other_types'))
                ->description(__('leave.other_types_desc'))
                ->schema([
                    Grid::make(3)->schema([
                        Toggle::make('marriage_used')
                            ->label(__('leave.marriage_used'))
                            ->inline(false),

                        TextInput::make('maternity_used')
                            ->label(__('leave.maternity_used'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(70)
                            ->required(),

                        TextInput::make('bereavement_used')
                            ->label(__('leave.bereavement_used'))
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(LeaveBalance::query()->with('user')->latest('year'))
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('leave.employee'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('year')
                    ->label(__('leave.year'))
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('years_of_service')
                    ->label(__('leave.years_of_service'))
                    ->badge()
                    ->color('info')
                    ->state(fn (LeaveBalance $r) => $r->user
                        ? app(LeaveService::class)->yearsOfService($r->user, $r->year)
                        : 0)
                    ->suffix(' ' . __('leave.years')),

                TextColumn::make('annual_entitlement')
                    ->label(__('leave.annual_entitlement'))
                    ->state(fn (LeaveBalance $r) => $r->user
                        ? app(LeaveService::class)->annualBracketDays($r->user, $r->year) . ' ' . __('leave.days')
                        : '—')
                    ->description(fn (LeaveBalance $r) => __('leave.annual_entitlement_desc', ['days' => (string) $r->annual_total])),

                TextColumn::make('annual_accrued')
                    ->label(__('leave.annual_accrued'))
                    ->state(fn (LeaveBalance $r) => $r->user
                        ? app(LeaveService::class)->accruedAnnual($r->user, $r->year) . ' ' . __('leave.days')
                        : '—')
                    ->description(fn (LeaveBalance $r) => __('leave.used') . ': ' . $r->annual_used . ' ' . __('leave.days'))
                    ->color('success'),

                TextColumn::make('annual_summary')
                    ->label(__('leave.annual'))
                    ->state(fn (LeaveBalance $r) => $r->annual_remaining . ' / ' . $r->annual_total)
                    ->description(fn (LeaveBalance $r) => __('leave.used') . ': ' . $r->annual_used . ' ' . __('leave.days'))
                    ->color(fn (LeaveBalance $r) => $r->annual_remaining <= 0 ? 'danger' : ($r->annual_remaining <= 3 ? 'warning' : 'success')),

                TextColumn::make('sick_summary')
                    ->label(__('leave.sick'))
                    ->state(fn (LeaveBalance $r) => $r->sick_remaining . ' / ' . $r->sick_total)
                    ->description(fn (LeaveBalance $r) => __('leave.used') . ': ' . $r->sick_used . ' ' . __('leave.days'))
                    ->color(fn (LeaveBalance $r) => $r->sick_remaining <= 0 ? 'danger' : 'success'),

                IconColumn::make('marriage_used')
                    ->label(__('leave.marriage'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),

                TextColumn::make('maternity_summary')
                    ->label(__('leave.maternity'))
                    ->state(fn (LeaveBalance $r) => $r->maternity_remaining . ' / 70')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('bereavement_used')
                    ->label(__('leave.bereavement'))
                    ->suffix(' ' . __('leave.events'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user.hire_date')
                    ->label(__('leave.hire_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('year')
                    ->label(__('leave.year'))
                    ->options(function () {
                        return LeaveBalance::distinct()
                            ->orderByDesc('year')
                            ->pluck('year', 'year')
                            ->toArray();
                    })
                    ->default(now()->year),
            ])
            ->headerActions([
                Action::make('init_year')
                    ->label(__('leave.init_year'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading(__('leave.init_year'))
                    ->modalDescription(__('leave.init_year_confirm', ['year' => now()->year]))
                    ->action(function () {
                        $leaveService = app(LeaveService::class);
                        $employees    = \App\Models\User::where('is_active', true)->get();
                        $created      = 0;
                        $skipped      = 0;

                        foreach ($employees as $employee) {
                            $exists = LeaveBalance::where('user_id', $employee->id)
                                ->where('year', now()->year)
                                ->exists();

                            if ($exists) {
                                $skipped++;
                                continue;
                            }

                            $leaveService->getOrCreateBalance($employee, now()->year);
                            $created++;
                        }

                        \Filament\Notifications\Notification::make()
                            ->title(__('leave.init_year_done', ['count' => $created]))
                            ->body($skipped > 0 ? __('leave.init_year_skipped', ['count' => $skipped]) : null)
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                EditAction::make()->label(__('general.edit')),
            ])
            ->defaultSort('year', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveBalances::route('/'),
            'edit'  => Pages\EditLeaveBalance::route('/{record}/edit'),
        ];
    }
}
