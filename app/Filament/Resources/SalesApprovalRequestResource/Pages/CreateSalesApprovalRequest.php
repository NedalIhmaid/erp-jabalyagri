<?php

namespace App\Filament\Resources\SalesApprovalRequestResource\Pages;

use App\Filament\Resources\SalesApprovalRequestResource;
use App\Services\AuditLogger;
use App\Services\SalesApprovalService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSalesApprovalRequest extends CreateRecord
{
    protected static string $resource = SalesApprovalRequestResource::class;

    protected string $view = 'filament.resources.sales-approval-requests.create-sales-approval-request';

    protected function handleRecordCreation(array $data): Model
    {
        return app(SalesApprovalService::class)->submit($data, auth()->user());
    }

    protected function afterCreate(): void
    {
        app(AuditLogger::class)->logSalesRequestCreated($this->record->fresh(['user', 'items']), auth()->user());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
