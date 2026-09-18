<?php

namespace App\Filament\Exports;

use App\Models\PaymentMethod;
use App\Models\SalesApprovalRequest;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class SalesApprovalRequestExporter extends Exporter
{
    protected static ?string $model = SalesApprovalRequest::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('request_number')
                ->label('رقم الطلب'),

            ExportColumn::make('user.name')
                ->label('المهندس'),

            ExportColumn::make('warehouseKeeper.name')
                ->label('أمين المستودع'),

            ExportColumn::make('client_name')
                ->label('اسم العميل'),

            ExportColumn::make('client_phone')
                ->label('هاتف العميل'),

            ExportColumn::make('client_address')
                ->label('عنوان العميل'),

            ExportColumn::make('region')
                ->label('المنطقة'),

            ExportColumn::make('project_type')
                ->label('نوع المشروع')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? $state),

            ExportColumn::make('project_size')
                ->label('حجم المشروع'),

            ExportColumn::make('payment_method')
                ->label('طريقة الدفع')
                ->formatStateUsing(fn ($state) => PaymentMethod::labelFor($state) ?? $state),

            ExportColumn::make('total_amount')
                ->label('المبلغ الإجمالي (JOD)'),

            ExportColumn::make('status')
                ->label('الحالة')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? $state),

            ExportColumn::make('current_stage')
                ->label('المرحلة الحالية')
                ->formatStateUsing(function ($state): string {
                    return match ((int) $state) {
                        1 => 'أمين المستودع',
                        2 => 'المدير المالي',
                        3 => 'مدير المشتريات',
                        default => "مرحلة {$state}",
                    };
                }),

            ExportColumn::make('engineer_notes')
                ->label('ملاحظات المهندس'),

            ExportColumn::make('rejection_reason')
                ->label('سبب الرفض'),

            ExportColumn::make('created_at')
                ->label('تاريخ الإنشاء'),

            ExportColumn::make('updated_at')
                ->label('تاريخ التحديث'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'اكتمل تصدير طلبات المبيعات. تم تصدير '.Number::format($export->successful_rows).' '.str('صف')->plural($export->successful_rows).' بنجاح.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' فشل تصدير '.Number::format($failedRowsCount).' صف.';
        }

        return $body;
    }
}
