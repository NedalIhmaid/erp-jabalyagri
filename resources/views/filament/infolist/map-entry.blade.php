@php
    $lat = (float) ($getRecord()->latitude ?? 0);
    $lng = (float) ($getRecord()->longitude ?? 0);
    $label   = e($getRecord()->client_name ?? '');
    $sublabel = e($getRecord()->location_text ?? '');
    $mapId   = 'visit-map-' . $getRecord()->id;
@endphp

@if($lat && $lng)
<div
    x-data="{
        mapId: '{{ $mapId }}',
        lat: {{ $lat }},
        lng: {{ $lng }},
        label: '{{ $label }}',
        sublabel: '{{ $sublabel }}',
        map: null,

        init() {
            this.loadLeaflet().then(() => this.renderMap());
        },

        loadLeaflet() {
            if (window.L) return Promise.resolve();
            return new Promise((resolve) => {
                if (!document.getElementById('leaflet-css')) {
                    const link = document.createElement('link');
                    link.id   = 'leaflet-css';
                    link.rel  = 'stylesheet';
                    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    document.head.appendChild(link);
                }
                if (!document.getElementById('leaflet-js')) {
                    const script = document.createElement('script');
                    script.id  = 'leaflet-js';
                    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    script.onload = resolve;
                    document.head.appendChild(script);
                } else {
                    const wait = setInterval(() => {
                        if (window.L) { clearInterval(wait); resolve(); }
                    }, 50);
                }
            });
        },

        renderMap() {
            const el = document.getElementById(this.mapId);
            if (!el || el._leaflet_id) return;
            this.map = L.map(el).setView([this.lat, this.lng], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href=\'https://www.openstreetmap.org/copyright\'>OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(this.map);
            const popup = this.sublabel
                ? `<b>${this.label}</b><br>${this.sublabel}`
                : `<b>${this.label}</b>`;
            L.marker([this.lat, this.lng])
                .addTo(this.map)
                .bindPopup(popup)
                .openPopup();
        }
    }"
    x-init="init()"
    wire:ignore
>
    <div
        class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-hidden"
    >
        <div class="fi-section-header px-6 py-4 border-b border-gray-100 dark:border-white/10 flex items-center gap-2">
            <x-heroicon-o-map-pin class="h-5 w-5 text-primary-500" />
            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                {{ __('general.location') }}
            </h3>
            <span class="text-xs text-gray-400 ms-auto">
                {{ number_format($lat, 6) }}, {{ number_format($lng, 6) }}
            </span>
        </div>
        <div id="{{ $mapId }}" style="height: 360px; width: 100%;"></div>
    </div>
</div>
@endif
