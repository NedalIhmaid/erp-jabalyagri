<?php

namespace App\Filament\Resources\LeaveBalanceResource\Pages;

use App\Filament\Resources\LeaveBalanceResource;
use App\Services\AdminSafetyService;
use App\Services\AuditLogger;
use Filament\Resources\Pages\EditRecord;

class EditLeaveBalance extends EditRecord
{
    protected static string $resource = LeaveBalanceResource::class;

    protected array $beforeState = [];

    public function getTitle(): string
    {
        $record = $this->getRecord();

        return __('leave.adjust_balance').' — '.$record->user?->name.' ('.$record->year.')';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        app(AdminSafetyService::class)->validateLeaveBalanceData($data);

        return $data;
    }

    protected function beforeSave(): void
    {
        $this->beforeState = app(AuditLogger::class)->leaveBalanceState($this->record->fresh('user'));
    }

    protected function afterSave(): void
    {
        app(AuditLogger::class)->logLeaveBalanceAdjusted(
            $this->record->fresh('user'),
            auth()->user(),
            $this->beforeState,
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
