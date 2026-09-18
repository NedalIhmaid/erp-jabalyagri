<!-- custom-user-edit-view -->
<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-slate-900/70">
            <p class="text-sm font-medium text-primary-600 dark:text-primary-400">{{ __('general.user_workspace') }}</p>
            <h2 class="mt-1 text-xl font-semibold text-slate-950 dark:text-white">{{ __('general.update_user_access') }}</h2>
            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                {{ __('general.update_user_access_desc') }}
            </p>
        </div>

        {{ $this->content }}
    </div>
</x-filament-panels::page>
