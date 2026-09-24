<x-filament-panels::page>
    @php($statuses = $this->configurationStatus())

    <div class="grid gap-4 md:grid-cols-5">
        @foreach ($statuses as $key => $status)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('whatsapp.diagnostics.status_'.$key) }}</div>
                <div class="mt-2 font-semibold {{ $status === 'ready' ? 'text-success-600' : ($status === 'warning' ? 'text-warning-600' : 'text-danger-600') }}">
                    {{ __('whatsapp.diagnostics.'.$status) }}
                </div>
            </div>
        @endforeach
    </div>

    <form wire:submit="sendTest" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-wrap gap-3">
            <x-filament::button type="submit" icon="heroicon-o-paper-airplane">
                {{ __('whatsapp.diagnostics.send_test') }}
            </x-filament::button>

            <x-filament::button type="button" color="gray" icon="heroicon-o-arrow-path" wire:click="clearConfigurationCache" wire:confirm="{{ __('whatsapp.diagnostics.clear_cache_confirmation') }}">
                {{ __('whatsapp.diagnostics.clear_cache') }}
            </x-filament::button>
        </div>
    </form>

    @if ($result)
        <div class="rounded-xl border p-5 {{ $result['success'] ? 'border-success-300 bg-success-50 dark:border-success-700 dark:bg-success-950' : 'border-danger-300 bg-danger-50 dark:border-danger-700 dark:bg-danger-950' }}">
            <h2 class="text-lg font-semibold">
                {{ $result['success'] ? __('whatsapp.diagnostics.sent_successfully') : __('whatsapp.diagnostics.send_failed') }}
            </h2>
            <dl class="mt-4 grid gap-3 md:grid-cols-2">
                <div><dt class="text-sm text-gray-500">{{ __('whatsapp.diagnostics.http_status') }}</dt><dd>{{ $result['status'] ?? '—' }}</dd></div>
                <div><dt class="text-sm text-gray-500">{{ __('whatsapp.diagnostics.recipient') }}</dt><dd dir="ltr">{{ $result['to'] }}</dd></div>
                <div><dt class="text-sm text-gray-500">{{ __('whatsapp.diagnostics.template') }}</dt><dd>{{ $result['template'] }}</dd></div>
                <div><dt class="text-sm text-gray-500">{{ __('whatsapp.diagnostics.message_id') }}</dt><dd class="break-all">{{ $result['message_id'] ?? '—' }}</dd></div>
            </dl>
            @if ($result['error'])
                <div class="mt-4 rounded-lg bg-white/60 p-3 text-sm dark:bg-black/20">
                    <div class="font-semibold">{{ __('whatsapp.diagnostics.meta_error') }}</div>
                    <div class="mt-1 break-words" dir="ltr">{{ $result['error'] }}</div>
                    @if ($result['details'])
                        <div class="mt-1 break-words" dir="ltr">{{ $result['details'] }}</div>
                    @endif
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>
