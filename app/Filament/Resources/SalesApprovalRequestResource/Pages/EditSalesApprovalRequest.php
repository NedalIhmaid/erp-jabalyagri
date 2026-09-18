<?php

namespace App\Filament\Resources\SalesApprovalRequestResource\Pages;

use App\Enums\SalesRequestStatus;
use App\Filament\Resources\SalesApprovalRequestResource;
use App\Services\AuditLogger;
use App\Services\SalesApprovalService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalesApprovalRequest extends EditRecord
{
    protected static string $resource = SalesApprovalRequestResource::class;

    protected string $view = 'filament.resources.sales-approval-requests.edit-sales-approval-request';

    protected array $beforeState = [];

    protected bool $resetWorkflowAfterSave = false;

    protected bool $warehouseKeeperChanged = false;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->using(function ($record): bool {
                    $before = app(AuditLogger::class)->salesRequestState($record->fresh(['user', 'items']));
                    $result = $record->delete();

                    if ($result) {
                        app(AuditLogger::class)->logSalesRequestDeleted($record, auth()->user(), $before);
                    }

                    return $result;
                }),
        ];
    }

    protected function beforeSave(): void
    {
        $this->beforeState = app(AuditLogger::class)->salesRequestState($this->record->fresh(['user', 'items']));
        $this->resetWorkflowAfterSave = $this->record->status === SalesRequestStatus::Returned;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->warehouseKeeperChanged = (int) ($data['warehouse_keeper_id'] ?? 0)
            !== (int) $this->record->warehouse_keeper_id;

        return $data;
    }

    protected function afterSave(): void
    {
        // Recalculate total amount
        $total = $this->record->items->sum('total_price');
        $this->record->update(['total_amount' => $total]);

        if ($this->resetWorkflowAfterSave || $this->warehouseKeeperChanged) {
            $record = $this->record->fresh(['user', 'items', 'warehouseKeeper']);
            $service = app(SalesApprovalService::class);
            $service->resetStages($record, auth()->user(), $this->beforeState);
            $service->notifyWarehouseKeeper($record->fresh('warehouseKeeper'));
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
