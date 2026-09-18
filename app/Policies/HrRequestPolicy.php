<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\HrRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class HrRequestPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:HrRequest');
    }

    public function view(AuthUser $authUser, HrRequest $hrRequest): bool
    {
        return $authUser->can('View:HrRequest');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:HrRequest');
    }

    public function update(AuthUser $authUser, HrRequest $hrRequest): bool
    {
        return $authUser->can('Update:HrRequest');
    }

    public function delete(AuthUser $authUser, HrRequest $hrRequest): bool
    {
        return $authUser->can('Delete:HrRequest');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:HrRequest');
    }

    public function restore(AuthUser $authUser, HrRequest $hrRequest): bool
    {
        return $authUser->can('Restore:HrRequest');
    }

    public function forceDelete(AuthUser $authUser, HrRequest $hrRequest): bool
    {
        return $authUser->can('ForceDelete:HrRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:HrRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:HrRequest');
    }

    public function replicate(AuthUser $authUser, HrRequest $hrRequest): bool
    {
        return $authUser->can('Replicate:HrRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:HrRequest');
    }

}