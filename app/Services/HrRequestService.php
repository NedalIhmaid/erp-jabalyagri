<?php

namespace App\Services;

use App\Enums\HrRequestStatus;
use App\Enums\HrRequestType;
use App\Models\HrRequest;
use App\Models\User;
use App\Notifications\HrRequestApprovedNotification;
use App\Notifications\HrRequestRejectedNotification;
use App\Notifications\HrRequestSubmittedNotification;
use Illuminate\Support\Facades\DB;

class HrRequestService
{
    public function __construct(
        protected LeaveService $leaveService,
        protected AuditLogger $auditLogger,
    ) {}

    /**
     * Submit a new HR request.
     */
    public function submit(array $data, User $user): HrRequest
    {
        $data['user_id'] = $user->id;
        $data['status'] = HrRequestStatus::Pending;
        $data['manager_id'] = $user->manager_id;

        $type = HrRequestType::from($data['type']);
        $days = $data['duration_days'] ?? 0;

        $gender = $user->gender?->value;
        if ($type === HrRequestType::MaternityLeave && $gender !== 'female') {
            throw new \InvalidArgumentException('Maternity leave is only available to female employees.');
        }
        if ($type === HrRequestType::PaternityLeave && $gender !== 'male') {
            throw new \InvalidArgumentException('Paternity leave is only available to male employees.');
        }

        if (! $this->leaveService->canTakeLeave($user, $type, $days)) {
            throw new \Exception('Insufficient leave balance');
        }

        $request = HrRequest::create($data);
        $this->auditLogger->logHrRequestSubmitted($request->fresh(['user', 'manager']), $user);
        $request->manager?->notify(new HrRequestSubmittedNotification($request));

        return $request;
    }

    /**
     * Approve as direct manager (Stage 1).
     */
    public function approveAsManager(HrRequest $request, User $user, ?string $comments = null): bool
    {
        $before = $this->auditLogger->hrRequestState($request);

        $result = DB::transaction(function () use ($request, $comments) {
            $request->update([
                'status' => HrRequestStatus::ManagerApproved,
                'manager_action_at' => now(),
                'manager_comments' => $comments,
            ]);

            return true;
        });

        // Notify all general managers that the request is waiting for final approval
        if ($result) {
            $request = $request->fresh(['user', 'manager']);
            $this->auditLogger->logHrManagerApproved($request, $user, $before);

            $generalManagers = User::whereHas('roles', fn ($q) => $q->where('name', 'general_manager'))
                ->where('is_active', true)
                ->get();
            foreach ($generalManagers as $gm) {
                $gm->notify(new HrRequestSubmittedNotification($request));
            }
        }

        return $result;
    }

    /**
     * Reject as direct manager (Stage 1).
     */
    public function rejectAsManager(HrRequest $request, User $user, ?string $comments = null): bool
    {
        $employee = $request->user;
        $before = $this->auditLogger->hrRequestState($request);

        $result = DB::transaction(function () use ($request, $comments) {
            $request->update([
                'status' => HrRequestStatus::Rejected,
                'manager_action_at' => now(),
                'manager_comments' => $comments,
            ]);

            return true;
        });

        if ($result) {
            $request = $request->fresh(['user', 'manager']);
            $this->auditLogger->logHrManagerRejected($request, $user, $before);

            $employee?->notify(
                new HrRequestRejectedNotification($request, $comments ?? '', $user->name)
            );
        }

        return $result;
    }

    /**
     * Approve as general manager (Stage 2 - final) and deduct leave.
     */
    public function approveAsGM(HrRequest $request, User $user, ?string $comments = null): bool
    {
        $employee = $request->user;
        $before = $this->auditLogger->hrRequestState($request);

        $result = DB::transaction(function () use ($request, $user, $comments) {
            $request->update([
                'status' => HrRequestStatus::Approved,
                'gm_action_at' => now(),
                'gm_comments' => $comments,
            ]);

            $this->leaveService->deductLeave(
                $request->user,
                $request->type,
                $request->duration_days ?? 0,
                $user,
                $request
            );

            return true;
        });

        if ($result) {
            $request = $request->fresh(['user', 'manager']);
            $this->auditLogger->logHrApproved($request, $user, $before);

            $employee?->notify(
                new HrRequestApprovedNotification($request, $user->name)
            );
        }

        return $result;
    }

    /**
     * Reject as general manager (Stage 2 - final).
     * If the request was already approved, restore the deducted leave balance.
     */
    public function rejectAsGM(HrRequest $request, User $user, ?string $comments = null): bool
    {
        $employee = $request->user;
        $wasApproved = $request->status === HrRequestStatus::Approved;
        $before = $this->auditLogger->hrRequestState($request);

        $result = DB::transaction(function () use ($request, $user, $comments, $wasApproved) {
            $request->update([
                'status' => HrRequestStatus::Rejected,
                'gm_action_at' => now(),
                'gm_comments' => $comments,
            ]);

            if ($wasApproved) {
                $this->leaveService->restoreLeave(
                    $request->user,
                    $request->type,
                    $request->duration_days ?? 0,
                    $user,
                    $request
                );
            }

            return true;
        });

        if ($result) {
            $request = $request->fresh(['user', 'manager']);
            $this->auditLogger->logHrRejected($request, $user, $before);

            $employee?->notify(
                new HrRequestRejectedNotification($request, $comments ?? '', $user->name)
            );
        }

        return $result;
    }

    /**
     * Check if user can approve as manager.
     */
    public function canApproveAsManager(HrRequest $request, User $user): bool
    {
        return $request->manager_id === $user->id
            && $request->status === HrRequestStatus::Pending;
    }

    /**
     * Check if user can approve as GM.
     */
    public function canApproveAsGM(HrRequest $request, User $user): bool
    {
        return $user->hasRole('general_manager')
            && $request->status === HrRequestStatus::ManagerApproved;
    }
}
