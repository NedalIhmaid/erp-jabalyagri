@php $rows = $this->getRows(); @endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('general.sales_per_engineer') }}
        </x-slot>

        @if ($rows->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
                {{ __('general.no_data') }}
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="py-2 text-start font-semibold text-gray-700 dark:text-gray-200 pe-4">
                                {{ __('general.engineer') }}
                            </th>
                            <th class="py-2 text-end font-semibold text-gray-700 dark:text-gray-200 pe-4">
                                {{ __('general.requests') }}
                            </th>
                            <th class="py-2 text-end font-semibold text-gray-700 dark:text-gray-200">
                                {{ __('general.total_sales') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($rows as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="py-2 pe-4">
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ $row->user?->name ?? '—' }}
                                    </span>
                                </td>
                                <td class="py-2 pe-4 text-end text-gray-600 dark:text-gray-400">
                                    {{ $row->requests_count }}
                                </td>
                                <td class="py-2 text-end font-semibold text-gray-900 dark:text-white">
                                    {{ number_format((float) $row->total_sales, 2) }}
                                    <span class="text-xs text-gray-400 ms-1">JOD</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
