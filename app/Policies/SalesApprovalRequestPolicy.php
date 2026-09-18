<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SalesApprovalRequest;
use App\Services\SalesApprovalService;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SalesApprovalRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SalesApprovalRequest');
    }

    public function view(AuthUser $authUser, SalesApprovalRequest $salesApprovalRequest): bool
    {
        if (! $authUser->can('View:SalesApprovalRequest')) {
            return false;
        }

        if ($authUser->hasRole('engineer') && ! $authUser->hasSalesApprovalRole()) {
            return $salesApprovalRequest->user_id === $authUser->id;
        }

        return true;
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SalesApprovalRequest');
    }

    public function update(AuthUser $authUser, SalesApprovalRequest $salesApprovalRequest): bool
    {
        if (! $authUser->can('Update:SalesApprovalRequest')) {
            return false;
        }

        if ($authUser->hasRole('engineer')) {
            return app(SalesApprovalService::class)->engineerCanMutateOwnRequest($salesApprovalRequest, $authUser);
        }

        return true;
    }

    public function delete(AuthUser $authUser, SalesApprovalRequest $salesApprovalRequest): bool
    {
        if ($authUser->hasRole('engineer')) {
            return app(SalesApprovalService::class)->engineerCanMutateOwnRequest($salesApprovalRequest, $authUser);
        }

        return $authUser->can('Delete:SalesApprovalRequest');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        if ($authUser->hasRole('engineer')) {
            return $authUser->can('Update:SalesApprovalRequest');
        }

        return $authUser->can('DeleteAny:SalesApprovalRequest');
    }

    public function restore(AuthUser $authUser, SalesApprovalRequest $salesApprovalRequest): bool
    {
        return $authUser->can('Restore:SalesApprovalRequest');
    }

    public function forceDelete(AuthUser $authUser, SalesApprovalRequest $salesApprovalRequest): bool
    {
        return $authUser->can('ForceDelete:SalesApprovalRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SalesApprovalRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SalesApprovalRequest');
    }

    public function replicate(AuthUser $authUser, SalesApprovalRequest $salesApprovalRequest): bool
    {
        return $authUser->can('Replicate:SalesApprovalRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SalesApprovalRequest');
    }
}
