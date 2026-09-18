<?php

namespace App\Filament\Pages;

use App\Enums\HrRequestStatus;
use App\Enums\HrRequestType;
use App\Models\HrRequest;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HrReports extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.hr-reports';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-document-chart-bar';
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.hr_reports');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.admin');
    }

    public static function getNavigationSort(): int
    {
        return 2;
    }

    public function getHeading(): string
    {
        return __('navigation.hr_reports');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('general_manager') ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label(__('general.export'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->modalHeading(__('general.export') . ' — ' . __('hr.title'))
                ->modalWidth('lg')
                ->form([
                    DatePicker::make('from')
                        ->label(__('general.from'))
                        ->native(false),

                    DatePicker::make('until')
                        ->label(__('general.until'))
                        ->native(false),

                    Select::make('status')
                        ->label(__('general.status'))
                        ->multiple()
                        ->options(fn () => collect(HrRequestStatus::cases())
                            ->mapWithKeys(fn ($s) => [$s->value => $s->getLabel()])),

                    Select::make('type')
                        ->label(__('hr.type'))
                        ->multiple()
                        ->options(fn () => collect(HrRequestType::cases())
                            ->mapWithKeys(fn ($t) => [$t->value => $t->getLabel()])),

                    Select::make('user_id')
                        ->label(__('general.employee'))
                        ->multiple()
                        ->searchable()
                        ->getSearchResultsUsing(
                            fn (string $search) => \App\Models\User::where('is_active', true)
                                ->where('name', 'like', "%{$search}%")
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->getOptionLabelsUsing(
                            fn (array $values) => \App\Models\User::whereIn('id', $values)
                                ->pluck('name', 'id')
                                ->toArray()
                        ),

                    Radio::make('format')
                        ->label(__('general.format'))
                        ->options(['csv' => 'CSV', 'xlsx' => 'Excel (XLSX)'])
                        ->default('xlsx')
                        ->inline(),
                ])
                ->action(function (array $data): void {
                    $params = array_filter($data, fn ($v) => filled($v));
                    $url    = route('reports.hr.export') . '?' . http_build_query($params);
                    $this->js("window.open(" . json_encode($url) . ", '_blank')");
                }),
        ];
    }

    public function getHrStats(): array
    {
        $approved = HrRequest::where('status', HrRequestStatus::Approved);
        $pending  = HrRequest::whereIn('status', [
            HrRequestStatus::Pending,
            HrRequestStatus::ManagerApproved,
        ]);

        return [
            'total_count'       => HrRequest::count(),
            'approved_count'    => (clone $approved)->count(),
            'approved_days'     => (float) (clone $approved)->sum('duration_days'),
            'pending_count'     => (clone $pending)->count(),
            'rejected_count'    => HrRequest::where('status', HrRequestStatus::Rejected)->count(),
            'this_month_count'  => HrRequest::whereMonth('created_at', now()->month)->count(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(HrRequest::query()->with(['user', 'manager']))
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('general.employee'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label(__('hr.type'))
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
                    ->sortable(),

                TextColumn::make('duration_days')
                    ->label(__('hr.duration_days'))
                    ->numeric()
                    ->summarize([
                        Sum::make()->label(__('general.total_days')),
                    ]),

                TextColumn::make('status')
                    ->label(__('general.status'))
                    ->badge()
                    ->color(fn ($state) => $state?->getColor())
                    ->formatStateUsing(fn ($state) => $state?->getLabel())
                    ->sortable(),

                TextColumn::make('manager.name')
                    ->label(__('general.direct_manager'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('general.submitted'))
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('general.status'))
                    ->options(fn () => collect(HrRequestStatus::cases())
                        ->mapWithKeys(fn ($s) => [$s->value => $s->getLabel()])),

                SelectFilter::make('type')
                    ->label(__('hr.type'))
                    ->options(fn () => collect(HrRequestType::cases())
                        ->mapWithKeys(fn ($t) => [$t->value => $t->getLabel()])),

                SelectFilter::make('user_id')
                    ->label(__('general.employee'))
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                \Filament\Tables\Filters\Filter::make('date_range')
                    ->label(__('general.date'))
                    ->form([
                        DatePicker::make('from')->label(__('general.from')),
                        DatePicker::make('until')->label(__('general.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = __('general.from') . ': ' . $data['from'];
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = __('general.until') . ': ' . $data['until'];
                        }

                        return $indicators;
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25);
    }
}
