<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: dejavusans, sans-serif;
        font-size: 11px;
        color: #1a1a1a;
        direction: rtl;
        line-height: 1.5;
    }

    /* ── Header ── */
    .header {
        width: 100%;
        border-bottom: 2px solid #16a34a;
        padding-bottom: 14px;
        margin-bottom: 18px;
    }
    .header-table { width: 100%; border-collapse: collapse; }
    .header-left  { text-align: right; vertical-align: top; }
    .header-right { text-align: left;  vertical-align: top; }
    .company-name {
        font-size: 20px;
        font-weight: bold;
        color: #15803d;
    }
    .company-sub {
        font-size: 10px;
        color: #6b7280;
        margin-top: 2px;
    }
    .doc-title {
        text-align: left;
    }
    .doc-title h1 {
        font-size: 16px;
        font-weight: bold;
        color: #111827;
    }
    .doc-title .req-number {
        font-size: 12px;
        color: #16a34a;
        font-weight: bold;
        margin-top: 3px;
    }
    .doc-title .req-date {
        font-size: 10px;
        color: #6b7280;
        margin-top: 2px;
    }

    /* ── Status badge ── */
    .status-badge {
        display: inline-block;
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #86efac;
        border-radius: 12px;
        padding: 2px 10px;
        font-size: 10px;
        font-weight: bold;
        margin-bottom: 16px;
    }

    /* ── Info grid ── */
    table.info-grid {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 18px;
        border: 1px solid #e5e7eb;
    }
    table.info-grid tr:nth-child(even) td {
        background: #f9fafb;
    }
    table.info-grid td {
        padding: 7px 12px;
        vertical-align: top;
    }
    .info-label {
        font-size: 9px;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin-bottom: 2px;
    }
    .info-value {
        font-size: 11px;
        color: #111827;
        font-weight: 500;
    }

    /* ── Section heading ── */
    .section-heading {
        font-size: 12px;
        font-weight: bold;
        color: #374151;
        border-right: 3px solid #16a34a;
        padding-right: 8px;
        margin-bottom: 10px;
        margin-top: 18px;
    }

    /* ── Items table ── */
    table.items {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 6px;
        font-size: 10.5px;
    }
    table.items thead tr {
        background: #15803d;
        color: white;
    }
    table.items thead th {
        padding: 7px 10px;
        text-align: right;
        font-weight: bold;
    }
    table.items tbody tr:nth-child(even) {
        background: #f0fdf4;
    }
    table.items tbody tr:hover {
        background: #dcfce7;
    }
    table.items tbody td {
        padding: 7px 10px;
        border-bottom: 1px solid #e5e7eb;
        vertical-align: middle;
    }
    table.items tfoot tr {
        background: #f9fafb;
        border-top: 2px solid #16a34a;
    }
    table.items tfoot td {
        padding: 8px 10px;
        font-weight: bold;
    }
    .total-amount {
        font-size: 13px;
        color: #15803d;
    }

    /* ── Approval stages ── */
    table.stages {
        width: 100%;
        border-collapse: collapse;
        font-size: 10px;
        margin-bottom: 18px;
    }
    table.stages thead tr {
        background: #374151;
        color: white;
    }
    table.stages thead th {
        padding: 6px 10px;
        text-align: right;
    }
    table.stages tbody tr:nth-child(even) {
        background: #f9fafb;
    }
    table.stages tbody td {
        padding: 6px 10px;
        border-bottom: 1px solid #e5e7eb;
    }
    .stage-approved { color: #16a34a; font-weight: bold; }
    .stage-rejected { color: #dc2626; font-weight: bold; }
    .stage-pending  { color: #d97706; }

    /* ── Notes ── */
    .notes-box {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 10px 12px;
        font-size: 10.5px;
        color: #374151;
        margin-bottom: 18px;
    }

    /* ── Signature block ── */
    table.signature-grid {
        width: 100%;
        border-collapse: collapse;
        margin-top: 14px;
        margin-bottom: 18px;
        page-break-inside: avoid;
    }
    table.signature-grid td.signature-cell {
        width: 16.66%;
        border: 1px solid #e5e7eb;
        padding: 10px 6px;
        vertical-align: top;
        text-align: center;
    }
    .signature-title {
        font-size: 9.5px;
        font-weight: bold;
        color: #374151;
        margin-bottom: 4px;
    }
    .signature-name {
        font-size: 9px;
        color: #6b7280;
        min-height: 11px;
        margin-bottom: 20px;
    }
    .signature-line {
        border-top: 1px dashed #9ca3af;
        padding-top: 3px;
        font-size: 8.5px;
        color: #9ca3af;
    }
    .signature-date {
        font-size: 8.5px;
        color: #6b7280;
        margin-top: 3px;
    }

    /* ── Footer ── */
    .footer {
        border-top: 1px solid #e5e7eb;
        padding-top: 10px;
        margin-top: 20px;
        width: 100%;
        font-size: 9px;
        color: #9ca3af;
    }
    .footer-table { width: 100%; border-collapse: collapse; }
    .footer-left  { text-align: right; }
    .footer-right { text-align: left; }
    .footer-seal {
        text-align: center;
        font-size: 9px;
        color: #9ca3af;
    }
</style>
</head>
<body>

{{-- ── Header ── --}}
@php
    $logoPath = public_path('images/logo-mark.svg');
    $hasLogo  = is_file($logoPath);
@endphp
<div class="header">
    <table class="header-table">
        <tr>
            @if($hasLogo)
            <td width="14%" style="vertical-align: middle;">
                <img src="{{ $logoPath }}" width="52" height="52" alt="Al-Jabali"/>
            </td>
            @endif
            <td class="header-left" width="{{ $hasLogo ? '46%' : '60%' }}">
                <div class="company-name">شركة الجبالي للزراعة</div>
                <div class="company-sub">Al-Jabali Agricultural Company</div>
            </td>
            <td class="header-right" width="40%">
                <div class="doc-title">
                    <h1>طلب مبيعات</h1>
                    <div class="req-number">{{ $request->request_number }}</div>
                    <div class="req-date">{{ $request->created_at->format('Y/m/d') }}</div>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ── Status ── --}}
@php
    $statusConfig = match($request->status?->value ?? '') {
        'approved' => ['label' => '✓ تمت الموافقة النهائية', 'bg' => '#dcfce7', 'color' => '#15803d', 'border' => '#86efac'],
        'cancelled' => ['label' => '✗ مرفوض', 'bg' => '#fee2e2', 'color' => '#dc2626', 'border' => '#fca5a5'],
        'returned' => ['label' => '↺ تم الإرجاع', 'bg' => '#fef3c7', 'color' => '#d97706', 'border' => '#fcd34d'],
        'in_progress' => ['label' => '⏳ قيد المعالجة', 'bg' => '#dbeafe', 'color' => '#2563eb', 'border' => '#93c5fd'],
        default => ['label' => 'معلق', 'bg' => '#f3f4f6', 'color' => '#6b7280', 'border' => '#d1d5db'],
    };
@endphp
<div class="status-badge" style="background: {{ $statusConfig['bg'] }}; color: {{ $statusConfig['color'] }}; border-color: {{ $statusConfig['border'] }};">
    {{ $statusConfig['label'] }}
</div>

{{-- ── Request details ── --}}
<div class="section-heading">تفاصيل الطلب</div>
<table class="info-grid">
    <tr>
        <td width="25%">
            <div class="info-label">رقم الطلب</div>
            <div class="info-value">{{ $request->request_number }}</div>
        </td>
        <td width="25%">
            <div class="info-label">المهندس</div>
            <div class="info-value">{{ $request->user?->name ?? '—' }}</div>
        </td>
        <td width="25%">
            <div class="info-label">أمين المستودع</div>
            <div class="info-value">{{ $request->warehouseKeeper?->name ?? '—' }}</div>
        </td>
        <td width="25%">
            <div class="info-label">تاريخ الطلب</div>
            <div class="info-value">{{ $request->created_at->format('Y/m/d') }}</div>
        </td>
    </tr>
    <tr>
        <td width="25%">
            <div class="info-label">اسم العميل</div>
            <div class="info-value">{{ $request->client_name }}</div>
        </td>
        <td width="25%">
            <div class="info-label">هاتف العميل</div>
            <div class="info-value">{{ $request->client_phone ?? '—' }}</div>
        </td>
        <td width="50%" colspan="2">
            <div class="info-label">عنوان العميل</div>
            <div class="info-value">{{ $request->client_address ?? '—' }}</div>
        </td>
    </tr>
    <tr>
        <td width="25%">
            <div class="info-label">المنطقة</div>
            <div class="info-value">{{ $request->region ?? '—' }}</div>
        </td>
        <td width="25%">
            <div class="info-label">نوع المشروع</div>
            <div class="info-value">{{ $request->project_type?->getLabel() ?? '—' }}</div>
        </td>
        <td width="25%">
            <div class="info-label">حجم المشروع</div>
            <div class="info-value">{{ $request->project_size ?? '—' }}</div>
        </td>
        <td width="25%">
            <div class="info-label">طريقة الدفع</div>
            <div class="info-value">{{ \App\Models\PaymentMethod::labelFor($request->payment_method) ?? '—' }}</div>
        </td>
    </tr>
</table>

{{-- ── Items ── --}}
<div class="section-heading">المنتجات</div>
<table class="items">
    <thead>
        <tr>
            <th>#</th>
            <th>المنتج</th>
            <th>الوحدة</th>
            <th>الكمية</th>
            <th>سعر الوحدة</th>
            <th>الإجمالي</th>
        </tr>
    </thead>
    <tbody>
        @foreach($request->items as $i => $item)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $item->display_product_name }}</td>
            <td>{{ $item->unit }}</td>
            <td>{{ number_format($item->quantity, 2) }}</td>
            <td>{{ number_format($item->unit_price, 2) }} JOD</td>
            <td>{{ number_format($item->total_price, 2) }} JOD</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" style="text-align:left;">المبلغ الإجمالي</td>
            <td class="total-amount">{{ number_format($request->total_amount, 2) }} JOD</td>
        </tr>
    </tfoot>
</table>

@if(filled($request->engineer_notes))
<div class="section-heading">ملاحظات المهندس</div>
<div class="notes-box">{{ $request->engineer_notes }}</div>
@endif

{{-- ── Footer ── --}}
<div class="footer">
    <table class="footer-table">
        <tr>
            <td class="footer-left" width="70%">تم الإنشاء بواسطة نظام الجبالي &bull; {{ now()->format('Y/m/d H:i') }}</td>
            <td class="footer-right" width="30%">{{ $request->request_number }}</td>
        </tr>
    </table>
</div>

</body>
</html>
