<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\DailyVisit;
use Illuminate\Auth\Access\HandlesAuthorization;

class DailyVisitPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DailyVisit');
    }

    public function view(AuthUser $authUser, DailyVisit $dailyVisit): bool
    {
        return $authUser->can('View:DailyVisit');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DailyVisit');
    }

    public function update(AuthUser $authUser, DailyVisit $dailyVisit): bool
    {
        return $authUser->can('Update:DailyVisit');
    }

    public function delete(AuthUser $authUser, DailyVisit $dailyVisit): bool
    {
        return $authUser->can('Delete:DailyVisit');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:DailyVisit');
    }

    public function restore(AuthUser $authUser, DailyVisit $dailyVisit): bool
    {
        return $authUser->can('Restore:DailyVisit');
    }

    public function forceDelete(AuthUser $authUser, DailyVisit $dailyVisit): bool
    {
        return $authUser->can('ForceDelete:DailyVisit');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DailyVisit');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DailyVisit');
    }

    public function replicate(AuthUser $authUser, DailyVisit $dailyVisit): bool
    {
        return $authUser->can('Replicate:DailyVisit');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DailyVisit');
    }

}