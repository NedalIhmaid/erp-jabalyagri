<?php

namespace App\Http\Controllers;

use App\Models\SalesApprovalRequest;
use Illuminate\Support\Facades\Auth;
use Mpdf\Mpdf;

class SalesRequestPdfController extends Controller
{
    public function download(SalesApprovalRequest $request): \Symfony\Component\HttpFoundation\Response
    {
        $user = Auth::user();

        $canAccess = $request->status === \App\Enums\SalesRequestStatus::Approved
            || $request->user_id === $user->id
            || $user->hasRole(['sales_manager', 'purchasing_manager', 'financial_manager', 'general_manager', 'warehouse_keeper']);

        abort_unless($canAccess, 403);

        $request->load(['user', 'items.product', 'items.productUnit', 'approvalStages.approver']);

        $html = view('pdf.sales-request', compact('request'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 12,
            'margin_bottom' => 12,
            'default_font' => 'dejavusans',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'useSubstitutions' => true,
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->WriteHTML($html);

        $filename = "SAR-{$request->request_number}.pdf";

        return response($mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
