<!-- custom-sales-view-view -->
<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-slate-900/70">
            <p class="text-sm font-medium text-primary-600 dark:text-primary-400">{{ __('general.sales_request') }}</p>
            <h2 class="mt-1 text-xl font-semibold text-slate-950 dark:text-white">{{ __('general.approval_summary') }}</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                {{ __('general.approval_summary_desc') }}
            </p>
        </div>

        {{ $this->content }}
    </div>
</x-filament-panels::page>
