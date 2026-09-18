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

        $visitsIndex  = \App\Filament\Resources\DailyVisitResource::getUrl('index');
        $visitsCreate = \App\Filament\Resources\DailyVisitResource::getUrl('create');
        $salesIndex   = \App\Filament\Resources\SalesApprovalRequestResource::getUrl('index');
        $salesCreate  = \App\Filament\Resources\SalesApprovalRequestResource::getUrl('create');
        $hrIndex      = \App\Filament\Resources\HrRequestResource::getUrl('index');
        $hrCreate     = \App\Filament\Resources\HrRequestResource::getUrl('create');
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
            <a href="{{ $visitsCreate }}"
               class="group flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-primary-500 hover:shadow-md dark:border-gray-700 dark:bg-gray-900 dark:hover:border-primary-400">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-primary-700 group-hover:bg-primary-600 group-hover:text-white dark:bg-primary-900/40 dark:text-primary-300">
                    <x-heroicon-o-map-pin class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('general.new_daily_visit') }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('general.visits') }}</p>
                </div>
            </a>

            <a href="{{ $salesCreate }}"
               class="group flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-primary-500 hover:shadow-md dark:border-gray-700 dark:bg-gray-900 dark:hover:border-primary-400">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-primary-700 group-hover:bg-primary-600 group-hover:text-white dark:bg-primary-900/40 dark:text-primary-300">
                    <x-heroicon-o-document-plus class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('general.new_sales_request') }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('general.sales') }}</p>
                </div>
            </a>

            <a href="{{ $hrCreate }}"
               class="group flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-primary-500 hover:shadow-md dark:border-gray-700 dark:bg-gray-900 dark:hover:border-primary-400">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-primary-700 group-hover:bg-primary-600 group-hover:text-white dark:bg-primary-900/40 dark:text-primary-300">
                    <x-heroicon-o-calendar-days class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('general.new_leave_request') }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('general.hr') }}</p>
                </div>
            </a>

            <a href="{{ $visitsIndex }}"
               class="group flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-gray-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-900">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <x-heroicon-o-list-bullet class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('general.my_visits') }}</p>
                </div>
            </a>

            <a href="{{ $salesIndex }}"
               class="group flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-gray-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-900">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <x-heroicon-o-list-bullet class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('general.my_sales_requests') }}</p>
                </div>
            </a>

            <a href="{{ $hrIndex }}"
               class="group flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:border-gray-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-900">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <x-heroicon-o-list-bullet class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('general.my_leave_requests') }}</p>
                </div>
            </a>
        </div>
    </div>

</x-filament-panels::page>
