<x-filament-panels::page>
    @php $stats = $this->getSalesStats(); @endphp

    {{-- ── KPI Cards ── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6 mb-2">

        <div class="rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4 col-span-2 sm:col-span-1 lg:col-span-2">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('general.total_sales') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">
                {{ number_format($stats['approved_amount'], 2) }}
                <span class="text-sm font-normal text-gray-400">JOD</span>
            </p>
            <p class="mt-1 text-xs text-gray-400">
                {{ __('general.this_month_prefix') }}
                {{ number_format($stats['this_month_amount'], 2) }} JOD
            </p>
        </div>

        <div class="rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('general.total') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ $stats['total_count'] }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ __('general.this_month_prefix') }} {{ $stats['this_month_count'] }}</p>
        </div>

        <div class="rounded-xl bg-white ring-1 ring-emerald-500/20 dark:bg-gray-900 dark:ring-emerald-500/20 p-4">
            <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400">{{ __('general.approved') }}</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['approved_count'] }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ __('general.requests') }}</p>
        </div>

        <div class="rounded-xl bg-white ring-1 ring-amber-500/20 dark:bg-gray-900 dark:ring-amber-500/20 p-4">
            <p class="text-xs font-medium text-amber-600 dark:text-amber-400">{{ __('general.pending') }}</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $stats['pending_count'] }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ __('general.in_progress') }}</p>
        </div>

    </div>

    {{-- ── Table ── --}}
    {{ $this->table }}
</x-filament-panels::page>
