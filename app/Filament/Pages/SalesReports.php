<?php

namespace App\Filament\Pages;

use App\Enums\SalesRequestStatus;
use App\Models\PaymentMethod;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalesReports extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.sales-reports';

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.sales_reports');
    }

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    public static function getNavigationSort(): int
    {
        return 1;
    }

    public function getHeading(): string
    {
        return __('navigation.sales_reports');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['sales_manager', 'general_manager', 'financial_manager']) ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label(__('general.export'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->modalHeading(__('general.export').' — '.__('sales.title'))
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
                        ->options(fn () => collect(SalesRequestStatus::cases())
                            ->mapWithKeys(fn ($s) => [$s->value => $s->getLabel()])),

                    Select::make('user_id')
                        ->label(__('general.engineer'))
                        ->multiple()
                        ->searchable()
                        ->getSearchResultsUsing(
                            fn (string $search): array => $this->getEngineerOptions($search)
                        )
                        ->getOptionLabelsUsing(
                            fn (array $values): array => $this->getEngineerQuery()
                                ->whereIn('id', $values)
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
                    $url = route('reports.sales.export').'?'.http_build_query($params);
                    $this->js('window.open('.json_encode($url).", '_blank')");
                }),
        ];
    }

    public function getSalesStats(): array
    {
        $base = $this->getScopedSalesReportQuery();
        $approved = (clone $base)->where('status', SalesRequestStatus::Approved);
        $pending = (clone $base)->whereIn('status', [
            SalesRequestStatus::Pending,
            SalesRequestStatus::InProgress,
        ]);

        return [
            'total_count' => (clone $base)->count(),
            'approved_count' => (clone $approved)->count(),
            'approved_amount' => (clone $approved)->sum('total_amount'),
            'pending_count' => (clone $pending)->count(),
            'this_month_amount' => (clone $approved)->whereMonth('created_at', now()->month)->sum('total_amount'),
            'this_month_count' => (clone $base)->whereMonth('created_at', now()->month)->count(),
        ];
    }

    public function table(Table $table): Table
    {
        $stageNames = [
            1 => __('general.stage_1_warehouse'),
            2 => __('general.stage_2_financial'),
            3 => __('general.stage_3_purchasing'),
        ];

        return $table
            ->query($this->getScopedSalesReportQuery()->with(['user', 'items'])->withCount('items'))
            ->columns([
                TextColumn::make('request_number')
                    ->label(__('sales.request_number'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('user.name')
                    ->label(__('general.engineer'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client_name')
                    ->label(__('sales.client_name'))
                    ->searchable(),

                TextColumn::make('payment_method')
                    ->label(__('general.payment_method'))
                    ->badge()
                    ->color(fn ($state) => PaymentMethod::colorFor($state))
                    ->formatStateUsing(fn ($state) => PaymentMethod::labelFor($state))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('items_count')
                    ->label(__('sales.items'))
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label(__('sales.total_amount'))
                    ->money('JOD')
                    ->sortable()
                    ->summarize([
                        Sum::make()->money('JOD')->label(__('general.total')),
                    ]),

                TextColumn::make('status')
                    ->label(__('general.status'))
                    ->badge()
                    ->color(fn ($state) => $state?->getColor())
                    ->formatStateUsing(fn ($state) => $state?->getLabel())
                    ->sortable(),

                TextColumn::make('current_stage')
                    ->label(__('sales.current_stage'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => $stageNames[(int) $state] ?? __('sales.stage')." {$state}")
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('general.date'))
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('general.status'))
                    ->options(fn () => collect(SalesRequestStatus::cases())
                        ->mapWithKeys(fn ($s) => [$s->value => $s->getLabel()])),

                SelectFilter::make('current_stage')
                    ->label(__('sales.stage'))
                    ->options($stageNames),

                SelectFilter::make('user_id')
                    ->label(__('general.engineer'))
                    ->options(fn (): array => $this->getEngineerOptions())
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Filter::make('date_range')
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
                            $indicators[] = __('general.from').': '.$data['from'];
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = __('general.until').': '.$data['until'];
                        }

                        return $indicators;
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25);
    }

    protected function getScopedSalesReportQuery(): Builder
    {
        return $this->applySalesManagerScope(SalesApprovalRequest::query());
    }

    protected function applySalesManagerScope(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user?->hasRole('sales_manager')) {
            return $query->whereHas('user', fn (Builder $q): Builder => $q->where('manager_id', $user->id));
        }

        return $query;
    }

    protected function getEngineerQuery(): Builder
    {
        $query = User::role('engineer');
        $user = auth()->user();

        if ($user?->hasRole('sales_manager')) {
            $query->where('manager_id', $user->id);
        }

        return $query;
    }

    protected function getEngineerOptions(?string $search = null): array
    {
        return $this->getEngineerQuery()
            ->when(filled($search), fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->limit(50)
            ->pluck('name', 'id')
            ->toArray();
    }
}
