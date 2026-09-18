<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use App\Models\SalesApprovalRequest;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesExportController extends Controller
{
    public function download(Request $request): StreamedResponse|Response
    {
        abort_unless($request->user()?->hasRole(['sales_manager', 'general_manager', 'financial_manager']), 403);

        $format = $request->input('format', 'csv');

        $query = SalesApprovalRequest::query()
            ->with(['user', 'warehouseKeeper', 'items'])
            ->when(
                $request->user()?->hasRole('sales_manager'),
                fn ($q) => $q->whereHas('user', fn ($userQuery) => $userQuery->where('manager_id', $request->user()->id))
            )
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('until'), fn ($q) => $q->whereDate('created_at', '<=', $request->until))
            ->when($request->filled('status'), fn ($q) => $q->whereIn('status', (array) $request->status))
            ->when($request->filled('user_id'), fn ($q) => $q->whereIn('user_id', (array) $request->user_id))
            ->orderBy('created_at', 'desc');

        $rows = $query->get();
        $fname = 'sales-report-'.now()->format('Y-m-d');

        if ($format === 'xlsx') {
            return $this->xlsx($rows, $fname);
        }

        return $this->csv($rows, $fname);
    }

    private function headers(): array
    {
        return [
            'رقم الطلب',
            'المهندس',
            'أمين المستودع',
            'اسم العميل',
            'هاتف العميل',
            'عنوان العميل',
            'طريقة الدفع',
            'عدد المنتجات',
            'المبلغ الإجمالي (JOD)',
            'الحالة',
            'المرحلة الحالية',
            'ملاحظات المهندس',
            'سبب الرفض',
            'تاريخ الإنشاء',
        ];
    }

    private function row(SalesApprovalRequest $r): array
    {
        static $stageNames = [
            1 => 'أمين المستودع',
            2 => 'المدير المالي',
            3 => 'مدير المشتريات',
        ];

        return [
            $r->request_number,
            $r->user?->name ?? '—',
            $r->warehouseKeeper?->name ?? '—',
            $r->client_name,
            $r->client_phone ?? '—',
            $r->client_address ?? '—',
            PaymentMethod::labelFor($r->payment_method) ?? '—',
            $r->items->count(),
            (float) $r->total_amount,
            $r->status?->getLabel() ?? '—',
            $stageNames[$r->current_stage] ?? $r->current_stage,
            $r->engineer_notes ?? '—',
            $r->rejection_reason ?? '—',
            $r->created_at->format('Y-m-d'),
        ];
    }

    private function csv($rows, string $fname): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            // BOM for Arabic in Excel
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');
            fputcsv($out, $this->headers());
            foreach ($rows as $r) {
                fputcsv($out, $this->row($r));
            }
            fclose($out);
        }, $fname.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function xlsx($rows, string $fname): Response
    {
        $path = tempnam(sys_get_temp_dir(), 'sales_export_').'.xlsx';

        $writer = new Writer;
        $writer->openToFile($path);

        $headerStyle = (new Style)->setFontBold();
        $writer->addRow(Row::fromValues($this->headers(), $headerStyle));

        foreach ($rows as $r) {
            $writer->addRow(Row::fromValues($this->row($r)));
        }

        $writer->close();

        return response()->download($path, $fname.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
