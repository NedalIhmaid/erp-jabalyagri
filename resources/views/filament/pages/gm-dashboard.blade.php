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

        $salesReports = \App\Filament\Pages\SalesReports::canAccess() ? \App\Filament\Pages\SalesReports::getUrl() : null;
        $hrReports    = \App\Filament\Pages\HrReports::canAccess() ? \App\Filament\Pages\HrReports::getUrl() : null;
        $auditLog     = \App\Filament\Pages\AuditLogPage::canAccess() ? \App\Filament\Pages\AuditLogPage::getUrl() : null;

        $usersIndex = $user?->can('ViewAny:User') ? \App\Filament\Resources\UserResource::getUrl('index') : null;
        $rolesIndex = $user?->can('ViewAny:Role') ? \App\Filament\Resources\RoleResource::getUrl('index') : null;

        $salesIndex  = \App\Filament\Resources\SalesApprovalRequestResource::getUrl('index');
        $hrIndex     = \App\Filament\Resources\HrRequestResource::getUrl('index');
        $visitsIndex = \App\Filament\Resources\DailyVisitResource::getUrl('index');

        $actions = array_values(array_filter([
            ['url' => $salesReports,       'label' => __('general.reports_sales'), 'icon' => 'heroicon-o-chart-bar',             'primary' => true],
            ['url' => $hrReports,          'label' => __('general.reports_hr'),    'icon' => 'heroicon-o-document-chart-bar',    'primary' => true],
            ['url' => $salesIndex,         'label' => __('general.all_sales_requests'), 'icon' => 'heroicon-o-clipboard-document-check'],
            ['url' => $hrIndex,            'label' => __('general.all_hr_requests'),    'icon' => 'heroicon-o-calendar-days'],
            ['url' => $visitsIndex,        'label' => __('general.all_visits'),         'icon' => 'heroicon-o-map-pin'],
            ['url' => $usersIndex,         'label' => __('general.manage_users'),       'icon' => 'heroicon-o-users'],
            ['url' => $rolesIndex,         'label' => __('general.manage_roles'),       'icon' => 'heroicon-o-shield-check'],
            ['url' => $auditLog,           'label' => __('general.audit_log'),          'icon' => 'heroicon-o-clipboard-document-list'],
        ], fn ($a) => filled($a['url'])));
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
            @foreach ($actions as $action)
                <a href="{{ $action['url'] }}"
                   @class([
                        'group flex items-center gap-3 rounded-xl border p-4 shadow-sm transition hover:shadow-md',
                        'border-gray-200 bg-white hover:border-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-primary-400' => $action['primary'] ?? false,
                        'border-gray-200 bg-white hover:border-gray-400 dark:border-gray-700 dark:bg-gray-900' => ! ($action['primary'] ?? false),
                   ])>
                    <div @class([
                        'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg',
                        'bg-primary-100 text-primary-700 group-hover:bg-primary-600 group-hover:text-white dark:bg-primary-900/40 dark:text-primary-300' => $action['primary'] ?? false,
                        'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' => ! ($action['primary'] ?? false),
                    ])>
                        <x-dynamic-component :component="$action['icon']" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $action['label'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>

</x-filament-panels::page>
