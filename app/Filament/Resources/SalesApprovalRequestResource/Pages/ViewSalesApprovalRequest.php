<?php

namespace App\Filament\Resources\SalesApprovalRequestResource\Pages;

use App\Filament\Resources\SalesApprovalRequestResource;
use App\Services\SalesApprovalService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSalesApprovalRequest extends ViewRecord
{
    protected static string $resource = SalesApprovalRequestResource::class;

    protected string $view = 'filament.resources.sales-approval-requests.view-sales-approval-request';

    protected function getHeaderActions(): array
    {
        return [
            ...SalesApprovalRequestResource::getRecordWorkflowActions(),
            Actions\Action::make('print_pdf')
                ->label(__('sales.print_pdf'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn ($record) => route('sales-requests.pdf', $record))
                ->openUrlInNewTab()
                ->visible(fn ($record) => SalesApprovalRequestResource::userCanAccessRequestPdf($record)),
            Actions\EditAction::make()
                ->visible(fn ($record) => auth()->user()
                    && app(SalesApprovalService::class)->engineerCanMutateOwnRequest($record, auth()->user())),
        ];
    }
}
