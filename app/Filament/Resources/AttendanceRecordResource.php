<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceType;
use App\Filament\Resources\AttendanceRecordResource\Pages;
use App\Models\AttendanceRecord;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Read-only log of check-ins and check-outs shared as WhatsApp locations.
 */
class AttendanceRecordResource extends Resource
{
    protected static ?string $model = AttendanceRecord::class;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.admin');
    }

    public static function getNavigationLabel(): string
    {
        return __('attendance.navigation');
    }

    public static function getNavigationSort(): int
    {
        return 4;
    }

    public static function getModelLabel(): string
    {
        return __('attendance.record');
    }

    public static function getPluralModelLabel(): string
    {
        return __('attendance.records');
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-finger-print';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('general_manager') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        $timezone = AttendanceRecord::timezone();

        return $table
            ->defaultSort('recorded_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('attendance.employee'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label(__('attendance.type'))
                    ->badge(),

                TextColumn::make('recorded_at')
                    ->label(__('attendance.recorded_at'))
                    ->dateTime('Y-m-d H:i', $timezone)
                    ->sortable(),

                TextColumn::make('label')
                    ->label(__('attendance.location'))
                    ->state(fn (AttendanceRecord $record): string => $record->label
                        ?? $record->address
                        ?? "{$record->latitude}, {$record->longitude}")
                    ->description(fn (AttendanceRecord $record): ?string => $record->label ? $record->address : null)
                    ->icon('heroicon-o-map-pin')
                    ->tooltip(__('attendance.open_map'))
                    ->url(fn (AttendanceRecord $record): string => $record->mapUrl(), shouldOpenInNewTab: true)
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('attendance.type'))
                    ->options(AttendanceType::class),

                SelectFilter::make('user_id')
                    ->label(__('attendance.employee'))
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('recorded_at')
                    ->form([
                        DatePicker::make('from')->label(__('general.from')),
                        DatePicker::make('until')->label(__('general.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date) => $q->where(
                            'recorded_at',
                            '>=',
                            Carbon::parse($date, $timezone)->startOfDay()->utc(),
                        ))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date) => $q->where(
                            'recorded_at',
                            '<=',
                            Carbon::parse($date, $timezone)->endOfDay()->utc(),
                        ))),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendanceRecords::route('/'),
        ];
    }
}
