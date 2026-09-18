<?php

namespace App\Filament\Exports;

use App\Models\HrRequest;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class HrRequestExporter extends Exporter
{
    protected static ?string $model = HrRequest::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('user.name')
                ->label('الموظف'),

            ExportColumn::make('type')
                ->label('نوع الطلب')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? $state),

            ExportColumn::make('start_date')
                ->label('تاريخ البداية'),

            ExportColumn::make('end_date')
                ->label('تاريخ النهاية'),

            ExportColumn::make('start_time')
                ->label('وقت البداية'),

            ExportColumn::make('end_time')
                ->label('وقت النهاية'),

            ExportColumn::make('duration_days')
                ->label('عدد الأيام'),

            ExportColumn::make('reason')
                ->label('السبب'),

            ExportColumn::make('status')
                ->label('الحالة')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? $state),

            ExportColumn::make('manager.name')
                ->label('المدير المباشر'),

            ExportColumn::make('manager_action_at')
                ->label('تاريخ إجراء المدير'),

            ExportColumn::make('manager_comments')
                ->label('تعليقات المدير'),

            ExportColumn::make('gm_action_at')
                ->label('تاريخ إجراء المدير العام'),

            ExportColumn::make('gm_comments')
                ->label('تعليقات المدير العام'),

            ExportColumn::make('created_at')
                ->label('تاريخ الإرسال'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'اكتمل تصدير طلبات الموارد البشرية. تم تصدير ' . Number::format($export->successful_rows) . ' ' . str('صف')->plural($export->successful_rows) . ' بنجاح.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' فشل تصدير ' . Number::format($failedRowsCount) . ' صف.';
        }

        return $body;
    }
}
