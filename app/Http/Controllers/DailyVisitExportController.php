<?php

namespace App\Http\Controllers;

use App\Models\DailyVisit;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DailyVisitExportController extends Controller
{
    public function download(Request $request): StreamedResponse|\Symfony\Component\HttpFoundation\Response
    {
        $format = $request->input('format', 'csv');

        $user = auth()->user();

        abort_unless($user?->hasRole(['engineer', 'sales_manager', 'general_manager']), 403);

        $query = DailyVisit::query()
            ->with('user')
            ->when($request->filled('from'), fn ($q) => $q->whereDate('visit_date', '>=', $request->from))
            ->when($request->filled('until'), fn ($q) => $q->whereDate('visit_date', '<=', $request->until))
            ->when($request->filled('user_id'), fn ($q) => $q->whereIn('user_id', (array) $request->user_id))
            ->orderBy('visit_date', 'desc');

        if ($user?->hasRole('engineer')) {
            $query->where('user_id', $user->id);
        } elseif ($user?->hasRole('sales_manager')) {
            $query->whereHas('user', fn ($q) => $q->where('manager_id', $user->id));
        }

        $rows  = $query->get();
        $fname = 'daily-visits-' . now()->format('Y-m-d');

        if ($format === 'xlsx') {
            return $this->xlsx($rows, $fname);
        }

        return $this->csv($rows, $fname);
    }

    private function headers(): array
    {
        return [
            __('general.date'),
            __('general.engineer'),
            __('general.farmer_name'),
            __('general.farmer_phone'),
            __('general.location'),
            __('general.latitude'),
            __('general.longitude'),
            __('general.visit_reason'),
            __('general.visit_results'),
            __('general.created'),
        ];
    }

    private function row(DailyVisit $v): array
    {
        return [
            $v->visit_date?->format('Y-m-d') ?? '—',
            $v->user?->name ?? '—',
            $v->client_name ?? '—',
            $v->client_phone ?? '—',
            $v->location_text ?? '—',
            $v->latitude !== null ? (float) $v->latitude : '—',
            $v->longitude !== null ? (float) $v->longitude : '—',
            $v->visit_reason ?? '—',
            $v->visit_results ?? '—',
            $v->created_at?->format('Y-m-d H:i') ?? '—',
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
        $path = tempnam(sys_get_temp_dir(), 'daily_visits_') . '.xlsx';

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
