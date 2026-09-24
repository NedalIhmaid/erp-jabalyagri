@php
    $isRtl = __('filament-panels::layout.direction') === 'rtl';
    $isSidebarCollapsibleOnDesktop = filament()->isSidebarCollapsibleOnDesktop();
    $isSidebarFullyCollapsibleOnDesktop = filament()->isSidebarFullyCollapsibleOnDesktop();
@endphp

@if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop)
    <div
        x-show="$store.sidebar.isOpen || @js($isSidebarCollapsibleOnDesktop)"
        class="fi-sidebar-nav-collapse-btn-ctn"
    >
        @if ($isSidebarCollapsibleOnDesktop)
            <x-filament::icon-button
                color="gray"
                :icon="$isRtl ? \Filament\Support\Icons\Heroicon::OutlinedChevronLeft : \Filament\Support\Icons\Heroicon::OutlinedChevronRight"
                icon-size="lg"
                :label="__('filament-panels::layout.actions.sidebar.expand.label')"
                x-cloak
                x-data="{}"
                x-on:click="$store.sidebar.open()"
                x-show="! $store.sidebar.isOpen"
                class="fi-sidebar-nav-open-collapse-btn"
            />
        @endif

        @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop)
            <x-filament::icon-button
                color="gray"
                :icon="$isRtl ? \Filament\Support\Icons\Heroicon::OutlinedChevronRight : \Filament\Support\Icons\Heroicon::OutlinedChevronLeft"
                icon-size="lg"
                :label="__('filament-panels::layout.actions.sidebar.collapse.label')"
                x-cloak
                x-data="{}"
                x-on:click="$store.sidebar.close()"
                x-show="$store.sidebar.isOpen"
                class="fi-sidebar-nav-close-collapse-btn"
            />
        @endif
    </div>
@endif
