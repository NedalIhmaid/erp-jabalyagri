<?php

use App\Http\Controllers\DailyVisitExportController;
use App\Http\Controllers\HrExportController;
use App\Http\Controllers\SalesExportController;
use App\Http\Controllers\SalesRequestPdfController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

// Filament admin panel is mounted at '/' in AdminPanelProvider
// No need for a welcome route - redirect to Filament login
Route::get('/welcome', function () {
    return view('welcome');
});

// Public legal pages — required by Meta for the WhatsApp Business app, so they
// must stay outside the auth middleware.
Route::get('/privacy', fn () => view('legal.privacy'))->name('legal.privacy');
Route::get('/terms', fn () => view('legal.terms'))->name('legal.terms');

// Meta calls these unauthenticated; CSRF is excluded in bootstrap/app.php.
Route::get('/whatsapp/webhook', [WhatsAppWebhookController::class, 'verify']);
Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle']);

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/sales-requests/{request}/pdf', [SalesRequestPdfController::class, 'download'])
        ->name('sales-requests.pdf');

    Route::get('/reports/sales/export', [SalesExportController::class, 'download'])
        ->name('reports.sales.export');

    Route::get('/reports/hr/export', [HrExportController::class, 'download'])
        ->name('reports.hr.export');

    Route::get('/reports/daily-visits/export', [DailyVisitExportController::class, 'download'])
        ->name('reports.daily-visits.export');
});

Route::middleware(['web', 'auth'])->get('/locale/switch', function () {
    $locale = auth()->user()->locale === 'ar' ? 'en' : 'ar';
    auth()->user()->update(['locale' => $locale]);

    return redirect()->back();
})->name('locale.switch');
