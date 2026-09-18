@php
    $stageLabel = match ((int) $record->current_stage) {
        1 => __('general.stage_1_warehouse'),
        2 => __('general.stage_2_financial'),
        3 => __('general.stage_3_purchasing'),
        default => __('general.unknown'),
    };
@endphp

<div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm dark:border-white/10 dark:bg-white/5">
    <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('sales.request_number') }}</dt>
            <dd class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $record->request_number }}</dd>
        </div>

        <div>
            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('general.status') }}</dt>
            <dd class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $record->status?->getLabel() ?? __('general.unknown') }}</dd>
        </div>

        <div>
            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('sales.client_name') }}</dt>
            <dd class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $record->client_name }}</dd>
        </div>

        <div>
            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('general.engineer') }}</dt>
            <dd class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $record->user?->name ?? __('general.unknown') }}</dd>
        </div>

        <div>
            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('sales.warehouse_keeper') }}</dt>
            <dd class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $record->warehouseKeeper?->name ?? __('general.unknown') }}</dd>
        </div>

        <div>
            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('sales.stage') }}</dt>
            <dd class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $stageLabel }}</dd>
        </div>

        <div>
            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('general.total') }}</dt>
            <dd class="mt-1 font-semibold text-gray-950 dark:text-white">{{ number_format((float) $record->total_amount, 2) }} JOD</dd>
        </div>
    </dl>
</div>
