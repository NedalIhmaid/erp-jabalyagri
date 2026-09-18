<?php

namespace App\Services;

use App\Models\HrRequest;
use App\Models\LeaveBalance;
use App\Models\SalesApprovalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class AuditLogger
{
    public function logSalesRequestCreated(SalesApprovalRequest $request, ?User $causer = null): void
    {
        $after = $this->salesRequestState($request);

        $this->write(
            logName: 'sales',
            description: 'sales.request.created',
            subject: $request,
            causer: $causer,
            summary: "Sales request {$request->request_number} submitted",
            before: [],
            after: $after,
            context: [
                'request_number' => $request->request_number,
                'engineer_name' => $request->user?->name,
                'subject_label' => $request->request_number,
            ],
        );
    }

    public function logSalesRequestResubmitted(SalesApprovalRequest $request, User $causer, array $before): void
    {
        $after = $this->salesRequestState($request);
        $changes = $this->diff($before, $after);

        if (! $changes) {
            return;
        }

        $this->write(
            logName: 'sales',
            description: 'sales.request.resubmitted',
            subject: $request,
            causer: $causer,
            summary: "Sales request {$request->request_number} resubmitted",
            before: $changes['before'],
            after: $changes['after'],
            context: [
                'request_number' => $request->request_number,
                'engineer_name' => $request->user?->name,
                'subject_label' => $request->request_number,
            ],
        );
    }

    public function logSalesStageApproved(SalesApprovalRequest $request, User $causer, int $stageNumber, array $before): void
    {
        $after = $this->salesRequestState($request);
        $changes = $this->diff($before, $after);

        $this->write(
            logName: 'sales',
            description: 'sales.stage.approved',
            subject: $request,
            causer: $causer,
            summary: "Sales request {$request->request_number} approved at stage {$stageNumber}",
            before: $changes['before'] ?? [],
            after: $changes['after'] ?? [],
            context: [
                'request_number' => $request->request_number,
                'engineer_name' => $request->user?->name,
                'stage_number' => $stageNumber,
                'subject_label' => $request->request_number,
            ],
        );
    }

    public function logSalesRequestRejected(SalesApprovalRequest $request, User $causer, array $before): void
    {
        $after = $this->salesRequestState($request);
        $changes = $this->diff($before, $after);

        $this->write(
            logName: 'sales',
            description: 'sales.request.rejected',
            subject: $request,
            causer: $causer,
            summary: "Sales request {$request->request_number} rejected",
            before: $changes['before'] ?? [],
            after: $changes['after'] ?? [],
            context: [
                'request_number' => $request->request_number,
                'engineer_name' => $request->user?->name,
                'subject_label' => $request->request_number,
            ],
        );
    }

    public function logSalesRequestReturned(SalesApprovalRequest $request, User $causer, array $before): void
    {
        $after = $this->salesRequestState($request);
        $changes = $this->diff($before, $after);

        $this->write(
            logName: 'sales',
            description: 'sales.request.returned',
            subject: $request,
            causer: $causer,
            summary: "Sales request {$request->request_number} returned to engineer",
            before: $changes['before'] ?? [],
            after: $changes['after'] ?? [],
            context: [
                'request_number' => $request->request_number,
                'engineer_name' => $request->user?->name,
                'subject_label' => $request->request_number,
            ],
        );
    }

    public function logSalesRequestDeleted(SalesApprovalRequest $request, User $causer, array $before): void
    {
        $this->write(
            logName: 'sales',
            description: 'sales.request.deleted',
            subject: $request,
            causer: $causer,
            summary: "Sales request {$request->request_number} deleted",
            before: $before,
            after: [],
            context: [
                'request_number' => $before['request_number'] ?? $request->request_number,
                'engineer_name' => $request->user?->name,
                'subject_label' => $before['request_number'] ?? $request->request_number,
            ],
            flags: ['subject_deleted' => true],
        );
    }

    public function logHrRequestSubmitted(HrRequest $request, ?User $causer = null): void
    {
        $after = $this->hrRequestState($request);

        $this->write(
            logName: 'hr',
            description: 'hr.request.submitted',
            subject: $request,
            causer: $causer,
            summary: "HR request #{$request->id} submitted",
            before: [],
            after: $after,
            context: [
                'employee_name' => $request->user?->name,
                'subject_label' => "#{$request->id}",
            ],
        );
    }

    public function logHrRequestUpdated(HrRequest $request, User $causer, array $before): void
    {
        $after = $this->hrRequestState($request);
        $changes = $this->diff($before, $after);

        if (! $changes) {
            return;
        }

        $this->write(
            logName: 'hr',
            description: 'hr.request.updated',
            subject: $request,
            causer: $causer,
            summary: "HR request #{$request->id} updated",
            before: $changes['before'],
            after: $changes['after'],
            context: [
                'employee_name' => $request->user?->name,
                'subject_label' => "#{$request->id}",
            ],
        );
    }

    public function logHrRequestDeleted(HrRequest $request, User $causer, array $before): void
    {
        $this->write(
            logName: 'hr',
            description: 'hr.request.deleted',
            subject: $request,
            causer: $causer,
            summary: "HR request #{$request->id} deleted",
            before: $before,
            after: [],
            context: [
                'employee_name' => data_get($before, 'employee.name', $request->user?->name),
                'subject_label' => "#{$request->id}",
            ],
            flags: ['subject_deleted' => true],
        );
    }

    public function logHrManagerApproved(HrRequest $request, User $causer, array $before): void
    {
        $after = $this->hrRequestState($request);
        $changes = $this->diff($before, $after);

        $this->write(
            logName: 'hr',
            description: 'hr.request.manager_approved',
            subject: $request,
            causer: $causer,
            summary: "HR request #{$request->id} approved by manager",
            before: $changes['before'] ?? [],
            after: $changes['after'] ?? [],
            context: [
                'employee_name' => $request->user?->name,
                'subject_label' => "#{$request->id}",
            ],
        );
    }

    public function logHrManagerRejected(HrRequest $request, User $causer, array $before): void
    {
        $after = $this->hrRequestState($request);
        $changes = $this->diff($before, $after);

        $this->write(
            logName: 'hr',
            description: 'hr.request.manager_rejected',
            subject: $request,
            causer: $causer,
            summary: "HR request #{$request->id} rejected by manager",
            before: $changes['before'] ?? [],
            after: $changes['after'] ?? [],
            context: [
                'employee_name' => $request->user?->name,
                'subject_label' => "#{$request->id}",
            ],
        );
    }

    public function logHrApproved(HrRequest $request, User $causer, array $before): void
    {
        $after = $this->hrRequestState($request);
        $changes = $this->diff($before, $after);

        $this->write(
            logName: 'hr',
            description: 'hr.request.approved',
            subject: $request,
            causer: $causer,
            summary: "HR request #{$request->id} approved",
            before: $changes['before'] ?? [],
            after: $changes['after'] ?? [],
            context: [
                'employee_name' => $request->user?->name,
                'subject_label' => "#{$request->id}",
            ],
        );
    }

    public function logHrRejected(HrRequest $request, User $causer, array $before): void
    {
        $after = $this->hrRequestState($request);
        $changes = $this->diff($before, $after);

        $this->write(
            logName: 'hr',
            description: 'hr.request.rejected',
            subject: $request,
            causer: $causer,
            summary: "HR request #{$request->id} rejected",
            before: $changes['before'] ?? [],
            after: $changes['after'] ?? [],
            context: [
                'employee_name' => $request->user?->name,
                'subject_label' => "#{$request->id}",
            ],
        );
    }

    public function logUserCreated(User $user, User $causer): void
    {
        $after = $this->userState($user);

        $this->write(
            logName: 'admin',
            description: 'admin.user.created',
            subject: $user,
            causer: $causer,
            summary: "User {$user->name} created",
            before: [],
            after: $after,
            context: [
                'user_name' => $user->name,
                'role_names' => $after['roles'],
                'subject_label' => $user->name,
            ],
        );
    }

    public function logUserUpdated(User $user, User $causer, array $before): void
    {
        $after = $this->userState($user);
        $changes = $this->diff($before, $after);

        if (! $changes) {
            return;
        }

        $this->write(
            logName: 'admin',
            description: 'admin.user.updated',
            subject: $user,
            causer: $causer,
            summary: "User {$user->name} updated",
            before: $changes['before'],
            after: $changes['after'],
            context: [
                'user_name' => $user->name,
                'role_names' => $after['roles'],
                'subject_label' => $user->name,
            ],
        );
    }

    public function logUserDeleted(User $user, User $causer, array $before): void
    {
        $this->write(
            logName: 'admin',
            description: 'admin.user.deleted',
            subject: $user,
            causer: $causer,
            summary: "User {$user->name} deleted",
            before: $before,
            after: [],
            context: [
                'user_name' => $before['name'] ?? $user->name,
                'role_names' => $before['roles'] ?? [],
                'subject_label' => $before['name'] ?? $user->name,
            ],
            flags: ['subject_deleted' => true],
        );
    }

    public function logRoleCreated(Role $role, User $causer): void
    {
        $after = $this->roleState($role);

        $this->write(
            logName: 'admin',
            description: 'admin.role.created',
            subject: $role,
            causer: $causer,
            summary: "Role {$role->name} created",
            before: [],
            after: $after,
            context: [
                'role_name' => $role->name,
                'subject_label' => $role->name,
            ],
        );
    }

    public function logRoleUpdated(Role $role, User $causer, array $before): void
    {
        $after = $this->roleState($role);
        $changes = $this->diff($before, $after);

        if (! $changes) {
            return;
        }

        $this->write(
            logName: 'admin',
            description: 'admin.role.updated',
            subject: $role,
            causer: $causer,
            summary: "Role {$role->name} updated",
            before: $changes['before'],
            after: $changes['after'],
            context: [
                'role_name' => $role->name,
                'subject_label' => $role->name,
            ],
        );
    }

    public function logRoleDeleted(Role $role, User $causer, array $before): void
    {
        $this->write(
            logName: 'admin',
            description: 'admin.role.deleted',
            subject: $role,
            causer: $causer,
            summary: "Role {$role->name} deleted",
            before: $before,
            after: [],
            context: [
                'role_name' => $before['name'] ?? $role->name,
                'subject_label' => $before['name'] ?? $role->name,
            ],
            flags: ['subject_deleted' => true],
        );
    }

    public function logLeaveBalanceAdjusted(
        LeaveBalance $balance,
        ?User $causer,
        array $before,
        string $description = 'leave.balance.adjusted',
        ?string $summary = null,
        array $context = [],
    ): void {
        $after = $this->leaveBalanceState($balance);
        $changes = $this->diff($before, $after);

        if (! $changes) {
            return;
        }

        $this->write(
            logName: 'leave',
            description: $description,
            subject: $balance,
            causer: $causer,
            summary: $summary ?? "Leave balance adjusted for {$balance->user?->name}",
            before: $changes['before'],
            after: $changes['after'],
            context: array_merge([
                'employee_name' => $balance->user?->name,
                'year' => $balance->year,
                'subject_label' => trim(($balance->user?->name ?? __('general.unknown')).' / '.$balance->year),
            ], $context),
        );
    }

    public function salesRequestState(SalesApprovalRequest $request): array
    {
        $request->loadMissing(['user', 'warehouseKeeper', 'items']);

        return [
            'request_number' => $request->request_number,
            'status' => $request->status?->value ?? $request->status,
            'current_stage' => (int) $request->current_stage,
            'warehouse_keeper' => [
                'id' => $request->warehouse_keeper_id,
                'name' => $request->warehouseKeeper?->name,
            ],
            'total_amount' => round((float) $request->total_amount, 2),
            'item_count' => $request->items->count(),
        ];
    }

    public function hrRequestState(HrRequest $request): array
    {
        $request->loadMissing(['user', 'manager']);

        return [
            'request_id' => (int) $request->id,
            'type' => $request->type?->value ?? $request->type,
            'status' => $request->status?->value ?? $request->status,
            'start_date' => $request->start_date?->toDateString(),
            'end_date' => $request->end_date?->toDateString(),
            'duration_days' => filled($request->duration_days) ? (float) $request->duration_days : null,
            'employee' => [
                'id' => $request->user_id,
                'name' => $request->user?->name,
            ],
            'manager' => [
                'id' => $request->manager_id,
                'name' => $request->manager?->name,
            ],
            'attachment_present' => filled($request->attachment),
        ];
    }

    public function userState(User $user): array
    {
        $user->loadMissing('roles');

        return [
            'user_id' => (int) $user->id,
            'name' => $user->name,
            'roles' => $user->roles->pluck('name')->sort()->values()->all(),
            'is_active' => (bool) $user->is_active,
            'locale' => $user->locale,
            'manager_id' => $user->manager_id ? (int) $user->manager_id : null,
            'hire_date' => $user->hire_date?->toDateString(),
        ];
    }

    public function roleState(Role $role): array
    {
        $role->loadCount('permissions');

        return [
            'name' => $role->name,
            'permission_count' => (int) $role->permissions_count,
        ];
    }

    public function leaveBalanceState(LeaveBalance $balance): array
    {
        $balance->loadMissing('user');

        return [
            'year' => (int) $balance->year,
            'employee' => [
                'id' => (int) $balance->user_id,
                'name' => $balance->user?->name,
            ],
            'annual_total' => (float) $balance->annual_total,
            'annual_used' => (float) $balance->annual_used,
            'sick_total' => (float) $balance->sick_total,
            'sick_used' => (float) $balance->sick_used,
            'marriage_used' => (bool) $balance->marriage_used,
            'maternity_used' => (int) $balance->maternity_used,
            'bereavement_used' => (int) $balance->bereavement_used,
        ];
    }

    public function diff(array $before, array $after): ?array
    {
        $changedBefore = [];
        $changedAfter = [];

        foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $key) {
            $beforeValue = $before[$key] ?? null;
            $afterValue = $after[$key] ?? null;

            if ($beforeValue === $afterValue) {
                continue;
            }

            $changedBefore[$key] = $beforeValue;
            $changedAfter[$key] = $afterValue;
        }

        if ($changedBefore === [] && $changedAfter === []) {
            return null;
        }

        return [
            'before' => $changedBefore,
            'after' => $changedAfter,
        ];
    }

    protected function write(
        string $logName,
        string $description,
        Model $subject,
        ?Model $causer,
        string $summary,
        array $before,
        array $after,
        array $context = [],
        array $flags = [],
    ): void {
        $activity = activity($logName)
            ->performedOn($subject)
            ->event($description)
            ->withProperties([
                'domain' => $logName,
                'summary' => $summary,
                'before' => $before,
                'after' => $after,
                'flags' => array_merge([
                    'redacted' => true,
                ], $flags),
                'context' => $context,
            ]);

        if ($causer) {
            $activity->causedBy($causer);
        }

        $activity->log($description);
    }
}
