<?php

namespace App\Services;

use App\Enums\ApprovalAction;
use App\Enums\SalesRequestStatus;
use App\Models\ApprovalStage;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use App\Notifications\SalesRequestApprovedNotification;
use App\Notifications\SalesRequestRejectedNotification;
use App\Notifications\SalesRequestReturnedNotification;
use App\Notifications\SalesRequestStageAdvancedNotification;
use App\Notifications\SalesRequestSubmittedNotification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesApprovalService
{
    protected const FINAL_STAGE = 3;

    protected const STAGE_ROLE_MAP = [
        1 => 'warehouse_keeper',
        2 => 'financial_manager',
        3 => 'purchasing_manager',
    ];

    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Submit a new sales request and initialize its approval stages.
     */
    public function submit(array $data, User $user): SalesApprovalRequest
    {
        $warehouseKeeper = $this->resolveWarehouseKeeper($data['warehouse_keeper_id'] ?? null);
        $payload = $data;
        $payload['user_id'] = $user->id;
        $payload['warehouse_keeper_id'] = $warehouseKeeper->id;
        $payload['status'] = SalesRequestStatus::InProgress;
        $payload['current_stage'] = 1;

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                $request = DB::transaction(function () use ($payload) {
                    $request = SalesApprovalRequest::create([
                        ...$payload,
                        'request_number' => $this->nextRequestNumber(),
                    ]);

                    $this->initializeStages($request);

                    return $request;
                });

                $this->notifyWarehouseKeeper($request->fresh('warehouseKeeper'));

                return $request;
            } catch (QueryException $exception) {
                if (! $this->isRequestNumberCollision($exception) || $attempt === 4) {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException('Unable to create sales request.');
    }

    /**
     * Approve a sales request at the current stage.
     */
    public function approve(SalesApprovalRequest $request, User $user, ?string $comments = null): bool
    {
        $request->refresh();

        if (! $this->canActOnRequest($request, $user)) {
            return false;
        }

        $currentStage = $request->current_stage;
        $before = $this->auditLogger->salesRequestState($request);

        if ($currentStage === self::FINAL_STAGE) {
            return $this->completeApproval($request, $user, ApprovalAction::Approved, $comments, $before);
        }

        $result = DB::transaction(function () use ($request, $user, $currentStage, $comments) {
            $this->recordAction($request, $currentStage, $user, ApprovalAction::Approved, $comments);

            $nextStage = $currentStage + 1;
            $request->update([
                'current_stage' => $nextStage,
                'status' => SalesRequestStatus::InProgress,
            ]);

            $this->createStageRecord($request->id, $nextStage);

            return true;
        });

        // Notify the next stage's approver after the transaction commits
        if ($result) {
            $request = $request->fresh(['user', 'items']);
            $this->auditLogger->logSalesStageApproved($request, $user, $currentStage, $before);

            $nextRole = self::STAGE_ROLE_MAP[$request->current_stage] ?? null;
            $nextApprover = $nextRole
                ? User::whereHas('roles', fn ($q) => $q->where('name', $nextRole))
                    ->where('is_active', true)
                    ->first()
                : null;

            $nextApprover?->notify(
                new SalesRequestStageAdvancedNotification($request->fresh(), $request->current_stage)
            );
        }

        return $result;
    }

    /**
     * Reject a sales request.
     */
    public function reject(SalesApprovalRequest $request, User $user, ?string $reason = null): bool
    {
        $request->refresh();

        if (! $this->canActOnRequest($request, $user)) {
            return false;
        }

        $engineer = $request->user;
        $before = $this->auditLogger->salesRequestState($request);

        $result = DB::transaction(function () use ($request, $user, $reason) {
            $this->recordAction($request, $request->current_stage, $user, ApprovalAction::Rejected, $reason);

            $request->update([
                'status' => SalesRequestStatus::Cancelled,
                'rejection_reason' => $reason,
                'rejected_by' => $user->id,
            ]);

            return true;
        });

        if ($result) {
            $request = $request->fresh(['user', 'items']);
            $this->auditLogger->logSalesRequestRejected($request, $user, $before);

            $engineer?->notify(
                new SalesRequestRejectedNotification($request, $reason ?? '', $user->name)
            );
        }

        return $result;
    }

    /**
     * Return a request to the engineer (warehouse keeper only).
     */
    public function returnToEngineer(SalesApprovalRequest $request, User $user, ?string $reason = null): bool
    {
        $request->refresh();

        if (! $this->canReturnRequest($request, $user)) {
            return false;
        }

        $engineer = $request->user;
        $before = $this->auditLogger->salesRequestState($request);

        $result = DB::transaction(function () use ($request, $user, $reason) {
            $this->recordAction($request, 1, $user, ApprovalAction::Returned, $reason);

            $request->update([
                'status' => SalesRequestStatus::Returned,
                'returned_by' => $user->id,
            ]);

            return true;
        });

        if ($result) {
            $request = $request->fresh(['user', 'items']);
            $this->auditLogger->logSalesRequestReturned($request, $user, $before);

            $engineer?->notify(
                new SalesRequestReturnedNotification($request, $reason ?? '', $user->name)
            );
        }

        return $result;
    }

    /**
     * Complete the final approval (Stage 3).
     */
    protected function completeApproval(SalesApprovalRequest $request, User $user, ApprovalAction $action, ?string $comments, array $before): bool
    {
        $engineer = $request->user;

        $result = DB::transaction(function () use ($request, $user, $action, $comments) {
            $this->recordAction($request, self::FINAL_STAGE, $user, $action, $comments);

            $request->update([
                'status' => $action === ApprovalAction::Approved
                    ? SalesRequestStatus::Approved
                    : SalesRequestStatus::Cancelled,
                'rejection_reason' => $action === ApprovalAction::Rejected ? $comments : $request->rejection_reason,
                'rejected_by' => $action === ApprovalAction::Rejected ? $user->id : $request->rejected_by,
            ]);

            return true;
        });

        if ($result) {
            $request = $request->fresh(['user', 'items']);
            $this->auditLogger->logSalesStageApproved($request, $user, self::FINAL_STAGE, $before);

            $engineer?->notify(
                new SalesRequestApprovedNotification($request)
            );
        }

        return $result;
    }

    /**
     * Record an action on a stage.
     */
    protected function recordAction(SalesApprovalRequest $request, int $stageNumber, User $user, ApprovalAction $action, ?string $comments): void
    {
        $stage = ApprovalStage::where('sales_approval_request_id', $request->id)
            ->where('stage_number', $stageNumber)
            ->first();

        if ($stage) {
            $stage->update([
                'action' => $action,
                'approver_id' => $user->id,
                'comments' => $comments,
                'acted_at' => now(),
            ]);
        }
    }

    /**
     * Ensure a stage record exists.
     */
    protected function createStageRecord(int $requestId, int $stageNumber): void
    {
        $role = self::STAGE_ROLE_MAP[$stageNumber] ?? null;

        if (! $role) {
            return;
        }

        if (ApprovalStage::where('sales_approval_request_id', $requestId)->where('stage_number', $stageNumber)->exists()) {
            return;
        }

        $request = SalesApprovalRequest::find($requestId);
        $approver = $this->approverForRole($role, $request);

        ApprovalStage::create([
            'sales_approval_request_id' => $requestId,
            'stage_number' => $stageNumber,
            'role' => $role,
            'approver_id' => $approver?->id,
            'action' => ApprovalAction::Pending,
        ]);
    }

    /**
     * Initialize all 3 approval stages for a new request.
     */
    public function initializeStages(SalesApprovalRequest $request): void
    {
        foreach (self::STAGE_ROLE_MAP as $stageNumber => $role) {
            $approver = $this->approverForRole($role, $request);

            ApprovalStage::create([
                'sales_approval_request_id' => $request->id,
                'stage_number' => $stageNumber,
                'role' => $role,
                'approver_id' => $approver?->id,
                'action' => ApprovalAction::Pending,
            ]);
        }
    }

    /**
     * Whether an engineer may edit or delete their own request before stage 1 is cleared,
     * or after it was returned for corrections.
     */
    public function engineerCanMutateOwnRequest(SalesApprovalRequest $request, User $user): bool
    {
        if (! $user->hasRole('engineer') || $request->user_id !== $user->id) {
            return false;
        }

        return $request->status === SalesRequestStatus::Returned
            || ($request->status === SalesRequestStatus::InProgress && $request->current_stage === 1);
    }

    /**
     * Check if a user can act on this request.
     */
    public function canActOnRequest(SalesApprovalRequest $request, User $user): bool
    {
        if ($request->status === SalesRequestStatus::Cancelled || $request->status === SalesRequestStatus::Approved) {
            return false;
        }

        $requiredRole = self::STAGE_ROLE_MAP[$request->current_stage] ?? null;
        $assignedWarehouseKeeperId = $this->assignedWarehouseKeeperId($request);

        if ($requiredRole === 'warehouse_keeper'
            && $assignedWarehouseKeeperId
            && $assignedWarehouseKeeperId !== $user->id) {
            return false;
        }

        return $requiredRole && $user->hasRole($requiredRole);
    }

    /**
     * Check if a user can return a request.
     */
    public function canReturnRequest(SalesApprovalRequest $request, User $user): bool
    {
        $assignedWarehouseKeeperId = $this->assignedWarehouseKeeperId($request);

        return $user->hasRole('warehouse_keeper')
            && (! $assignedWarehouseKeeperId || $assignedWarehouseKeeperId === $user->id)
            && $request->current_stage === 1
            && $request->status === SalesRequestStatus::InProgress;
    }

    /**
     * Reset stages for a resubmitted request.
     */
    public function resetStages(SalesApprovalRequest $request, ?User $user = null, ?array $before = null): void
    {
        $before ??= $this->auditLogger->salesRequestState($request);

        ApprovalStage::where('sales_approval_request_id', $request->id)->delete();

        $this->initializeStages($request);

        $request->update([
            'current_stage' => 1,
            'status' => SalesRequestStatus::InProgress,
            'rejection_reason' => null,
            'rejected_by' => null,
            'returned_by' => null,
        ]);

        if ($user) {
            $this->auditLogger->logSalesRequestResubmitted($request->fresh(['user', 'items']), $user, $before);
        }
    }

    protected function nextRequestNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = "SAR-{$date}-";

        $maxSequence = SalesApprovalRequest::query()
            ->where('request_number', 'like', "{$prefix}%")
            ->pluck('request_number')
            ->map(fn (string $requestNumber): int => (int) substr($requestNumber, strrpos($requestNumber, '-') + 1))
            ->max() ?? 0;

        return $prefix.str_pad((string) ($maxSequence + 1), 4, '0', STR_PAD_LEFT);
    }

    protected function isRequestNumberCollision(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $message = strtolower($exception->getMessage());

        return in_array($sqlState, ['23000', '23505'], true)
            && str_contains($message, 'sales_approval_requests')
            && str_contains($message, 'request_number');
    }

    public function notifyWarehouseKeeper(SalesApprovalRequest $request): void
    {
        $warehouseKeeper = $request->warehouseKeeper;

        if ($warehouseKeeper?->is_active) {
            $warehouseKeeper->notify(new SalesRequestSubmittedNotification($request));
        }
    }

    protected function resolveWarehouseKeeper(mixed $warehouseKeeperId): User
    {
        $query = User::role('warehouse_keeper')->where('is_active', true);
        $warehouseKeeper = filled($warehouseKeeperId)
            ? (clone $query)->find($warehouseKeeperId)
            : $query->first();

        if (! $warehouseKeeper) {
            throw ValidationException::withMessages([
                'warehouse_keeper_id' => __('sales.invalid_warehouse_keeper'),
            ]);
        }

        return $warehouseKeeper;
    }

    protected function approverForRole(string $role, ?SalesApprovalRequest $request = null): ?User
    {
        if ($role === 'warehouse_keeper' && $request?->warehouseKeeper) {
            return $request->warehouseKeeper;
        }

        return User::role($role)->where('is_active', true)->first();
    }

    protected function assignedWarehouseKeeperId(SalesApprovalRequest $request): ?int
    {
        if ($request->warehouse_keeper_id) {
            return (int) $request->warehouse_keeper_id;
        }

        $approverId = $request->approvalStages()
            ->where('stage_number', 1)
            ->value('approver_id');

        return $approverId ? (int) $approverId : null;
    }
}
