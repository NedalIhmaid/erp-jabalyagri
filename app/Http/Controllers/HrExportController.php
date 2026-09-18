<?php

namespace App\Http\Controllers;

use App\Enums\HrRequestStatus;
use App\Models\HrRequest;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HrExportController extends Controller
{
    public function download(Request $request): StreamedResponse|\Symfony\Component\HttpFoundation\Response
    {
        abort_unless($request->user()?->hasRole('general_manager'), 403);

        $format = $request->input('format', 'csv');

        $query = HrRequest::query()
            ->with(['user', 'manager'])
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('until'), fn ($q) => $q->whereDate('created_at', '<=', $request->until))
            ->when($request->filled('status'), fn ($q) => $q->whereIn('status', (array) $request->status))
            ->when($request->filled('user_id'), fn ($q) => $q->whereIn('user_id', (array) $request->user_id))
            ->when($request->filled('type'), fn ($q) => $q->whereIn('type', (array) $request->type))
            ->orderBy('created_at', 'desc');

        $rows  = $query->get();
        $fname = 'hr-report-' . now()->format('Y-m-d');

        if ($format === 'xlsx') {
            return $this->xlsx($rows, $fname);
        }

        return $this->csv($rows, $fname);
    }

    private function headers(): array
    {
        return [
            'الموظف',
            'نوع الطلب',
            'تاريخ البداية',
            'تاريخ النهاية',
            'وقت البداية',
            'وقت النهاية',
            'عدد الأيام',
            'السبب',
            'الحالة',
            'المدير المباشر',
            'تاريخ إجراء المدير',
            'تعليقات المدير',
            'تاريخ إجراء المدير العام',
            'تعليقات المدير العام',
            'تاريخ الإرسال',
        ];
    }

    private function row(HrRequest $r): array
    {
        return [
            $r->user?->name ?? '—',
            $r->type?->getLabel() ?? '—',
            $r->start_date?->format('Y-m-d') ?? '—',
            $r->end_date?->format('Y-m-d') ?? '—',
            $r->start_time?->format('H:i') ?? '—',
            $r->end_time?->format('H:i') ?? '—',
            (float) ($r->duration_days ?? 0),
            $r->reason ?? '—',
            $r->status?->getLabel() ?? '—',
            $r->manager?->name ?? '—',
            $r->manager_action_at?->format('Y-m-d') ?? '—',
            $r->manager_comments ?? '—',
            $r->gm_action_at?->format('Y-m-d') ?? '—',
            $r->gm_comments ?? '—',
            $r->created_at->format('Y-m-d'),
        ];
    }

    private function csv($rows, string $fname): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');
            fputcsv($out, $this->headers());
            foreach ($rows as $r) {
                fputcsv($out, $this->row($r));
            }
            fclose($out);
        }, $fname . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function xlsx($rows, string $fname): \Symfony\Component\HttpFoundation\Response
    {
        $path = tempnam(sys_get_temp_dir(), 'hr_export_') . '.xlsx';

        $writer = new Writer();
        $writer->openToFile($path);

        $headerStyle = (new Style())->setFontBold();
        $writer->addRow(Row::fromValues($this->headers(), $headerStyle));

        foreach ($rows as $r) {
            $writer->addRow(Row::fromValues($this->row($r)));
        }

        $writer->close();

        return response()->download($path, $fname . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
