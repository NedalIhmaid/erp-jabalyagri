@php
    $localeIsAr = (auth()->user()?->locale ?? app()->getLocale()) === 'ar';
    $languageSwitchLabel = $localeIsAr ? 'English' : 'عربي';
@endphp

<div class="fi-topbar-quick-actions">
    <x-filament::button
        :href="route('locale.switch')"
        color="gray"
        icon-size="sm"
        outlined
        size="sm"
        :icon="\Filament\Support\Icons\Heroicon::OutlinedLanguage"
        tag="a"
    >
        {{ $languageSwitchLabel }}
    </x-filament::button>

    <span class="fi-topbar-quick-actions-divider" aria-hidden="true"></span>

    <x-filament::icon-button
        color="gray"
        :href="filament()->getProfileUrl()"
        :icon="\Filament\Support\Icons\Heroicon::OutlinedCog6Tooth"
        icon-size="lg"
        :label="__('general.settings')"
        tag="a"
    />

    @if (filament()->hasDarkMode() && (! filament()->hasDarkModeForced()))
        <div class="fi-topbar-theme-toggle inline-flex" x-data="{}" x-cloak>
            <div x-show="$store.theme === 'light'">
                <x-filament::icon-button
                    color="gray"
                    :icon="\Filament\Support\Icons\Heroicon::OutlinedMoon"
                    icon-size="lg"
                    :label="__('filament-panels::layout.actions.theme_switcher.dark.label')"
                    x-on:click="window.dispatchEvent(new CustomEvent('theme-changed', { detail: 'dark' }))"
                />
            </div>
            <div x-show="$store.theme === 'dark'">
                <x-filament::icon-button
                    color="gray"
                    :icon="\Filament\Support\Icons\Heroicon::OutlinedSun"
                    icon-size="lg"
                    :label="__('filament-panels::layout.actions.theme_switcher.light.label')"
                    x-on:click="window.dispatchEvent(new CustomEvent('theme-changed', { detail: 'light' }))"
                />
            </div>
        </div>
    @endif

    <form action="{{ filament()->getLogoutUrl() }}" method="post" class="flex">
        @csrf

        <x-filament::icon-button
            color="gray"
            :icon="\Filament\Support\Icons\Heroicon::OutlinedArrowLeftEndOnRectangle"
            icon-size="lg"
            :label="__('filament-panels::layout.actions.logout.label')"
            tag="button"
            type="submit"
        />
    </form>
</div>
