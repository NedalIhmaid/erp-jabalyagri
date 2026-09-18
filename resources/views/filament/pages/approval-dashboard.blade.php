<x-filament-panels::page>
    @php
        $hour = now()->hour;
        $greeting = match(true) {
            $hour >= 5 && $hour < 12  => __('general.good_morning'),
            $hour >= 12 && $hour < 17 => __('general.good_afternoon'),
            default                   => __('general.good_evening'),
        };
        $user     = auth()->user();
        $roleName = __('roles.' . ($user?->getRoleNames()->first() ?? ''));
        $today    = now()->locale(app()->getLocale())->isoFormat('dddd، D MMMM YYYY');

        $isSalesManager      = $user?->hasRole('sales_manager') ?? false;
        $isWarehouseKeeper   = $user?->hasRole('warehouse_keeper') ?? false;
        $isPurchasingManager = $user?->hasRole('purchasing_manager') ?? false;
        $isFinancialManager  = $user?->hasRole('financial_manager') ?? false;

        $salesIndex   = \App\Filament\Resources\SalesApprovalRequestResource::getUrl('index');
        $hrIndex      = \App\Filament\Resources\HrRequestResource::getUrl('index');
        $salesReports = \App\Filament\Pages\SalesReports::canAccess()
            ? \App\Filament\Pages\SalesReports::getUrl()
            : null;
    @endphp

    {{-- Welcome banner --}}
    <div class="mb-6 rounded-2xl bg-gradient-to-br from-primary-600 to-primary-800 p-6 text-white shadow-lg dark:from-primary-700 dark:to-primary-900">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-primary-200">{{ $greeting }}،</p>
                <h2 class="mt-1 text-2xl font-bold">{{ $user?->name }}</h2>
                <p class="mt-0.5 text-sm text-primary-200">{{ $roleName }}</p>
            </div>
            <div class="mt-3 text-end sm:mt-0">
                <p class="text-sm text-primary-200">{{ $today }}</p>
                <p class="mt-1 text-xs text-primary-300">{{ __('general.welcome_back') }}</p>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="mb-6">
        <h3 class="mb-3 text-base font-semibold text-gray-900 dark:text-white">
            {{ __('general.quick_actions') }}
        </h3>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <a href="{{ $salesIndex }}"
               class="group flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-primary-500 hover:shadow-md dark:border-gray-700 dark:bg-gray-900 dark:hover:border-primary-400">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-primary-700 group-hover:bg-primary-600 group-hover:text-white dark:bg-primary-900/40 dark:text-primary-300">
                    <x-heroicon-o-clipboard-document-check class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('general.review_sales_requests') }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('general.approval_queue') }}</p>
                </div>
            </a>

            @if ($isSalesManager)
                <a href="{{ $hrIndex }}"
                   class="group flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-primary-500 hover:shadow-md dark:border-gray-700 dark:bg-gray-900 dark:hover:border-primary-400">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-primary-700 group-hover:bg-primary-600 group-hover:text-white dark:bg-primary-900/40 dark:text-primary-300">
                        <x-heroicon-o-calendar-days class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('general.review_hr_requests') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('general.hr') }}</p>
                    </div>
                </a>
            @endif

            @if ($salesReports)
                <a href="{{ $salesReports }}"
                   class="group flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-gray-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        <x-heroicon-o-chart-bar class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('general.reports_sales') }}</p>
                    </div>
                </a>
            @endif
        </div>
    </div>

    {{-- My Engineers (sales_manager only) --}}
    @if ($isSalesManager)
        @php $engineers = $this->getMyEngineers(); @endphp

        <div class="mb-6">
            <h3 class="mb-3 text-base font-semibold text-gray-900 dark:text-white">
                {{ __('general.engineers') }}
            </h3>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                @if ($engineers->isEmpty())
                    <p class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        {{ __('general.no_engineers_assigned') }}
                    </p>
                @else
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-start font-medium text-gray-700 dark:text-gray-300">{{ __('general.name') }}</th>
                                <th class="px-4 py-3 text-start font-medium text-gray-700 dark:text-gray-300">{{ __('general.phone') }}</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-700 dark:text-gray-300">{{ __('general.visits_this_month') }}</th>
                                <th class="px-4 py-3 text-center font-medium text-gray-700 dark:text-gray-300">{{ __('general.pending_sales_requests') }}</th>
                                <th class="px-4 py-3 text-end font-medium text-gray-700 dark:text-gray-300"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($engineers as $engineer)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $engineer->name }}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $engineer->phone ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-900/40 dark:text-primary-300">
                                            {{ $engineer->visits_this_month_count }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span @class([
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                            'bg-amber-50 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' => $engineer->pending_sales_count > 0,
                                            'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' => $engineer->pending_sales_count === 0,
                                        ])>
                                            {{ $engineer->pending_sales_count }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-end">
                                        @if (auth()->user()?->can('View:User'))
                                            <a href="{{ \App\Filament\Resources\UserResource::getUrl('view', ['record' => $engineer]) }}"
                                               class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                                {{ __('general.view_details') }}
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    @endif

</x-filament-panels::page>
