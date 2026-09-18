@php
    $statePath = isset($statePath) && is_string($statePath) && $statePath !== ''
        ? $statePath
        : 'data';

    if (isset($getStatePath) && is_callable($getStatePath)) {
        $resolvedStatePath = $getStatePath();

        if (is_string($resolvedStatePath) && $resolvedStatePath !== '') {
            $statePath = $resolvedStatePath;
        }
    }
@endphp

<div
    x-data="{
        busy: false,
        message: '',
        tone: 'neutral',

        capture() {
            if (! navigator.geolocation) {
                this.message = @js(__('general.location_unavailable'));
                this.tone = 'error';
                return;
            }

            this.busy = true;
            this.tone = 'neutral';
            this.message = @js(__('general.getting_location'));

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const lat = Number(pos.coords.latitude).toFixed(7);
                    const lng = Number(pos.coords.longitude).toFixed(7);
                    $wire.set(@js($statePath . '.latitude'), lat, false);
                    $wire.set(@js($statePath . '.longitude'), lng, false);
                    this.busy = false;
                    this.tone = 'success';
                    this.message = @js(__('general.location_captured'));
                },
                (err) => {
                    this.busy = false;
                    this.tone = 'error';
                    this.message = err.code === 1
                        ? @js(__('general.location_denied'))
                        : @js(__('general.location_unavailable'));
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
            );
        },
    }"
    class="flex flex-col gap-2"
>
    <button
        type="button"
        x-on:click="capture()"
        x-bind:disabled="busy"
        class="fi-btn fi-btn-color-primary fi-btn-size-md inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 disabled:opacity-60 disabled:cursor-wait"
    >
        <x-heroicon-m-map-pin class="h-4 w-4" />
        <span>{{ __('general.use_current_location') }}</span>
    </button>

    <p
        x-show="message"
        x-text="message"
        x-cloak
        x-bind:class="{
            'text-emerald-600 dark:text-emerald-400': tone === 'success',
            'text-red-600 dark:text-red-400': tone === 'error',
            'text-gray-500 dark:text-gray-400': tone === 'neutral',
        }"
        class="text-xs"
    ></p>
</div>
