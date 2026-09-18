<?php

namespace App\Services;

use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class AdminSafetyService
{
    private const CORE_ROLE_NAMES = [
        'engineer',
        'warehouse_keeper',
        'sales_manager',
        'purchasing_manager',
        'financial_manager',
        'general_manager',
    ];

    public function getCoreRoleNames(): array
    {
        return self::CORE_ROLE_NAMES;
    }

    public function ensureUserDeletionAllowed(User $target, ?User $actor = null): void
    {
        if ($actor && $target->is($actor)) {
            $this->haltWithMessage(__('admin.cannot_delete_yourself'));
        }

        if ($this->isLastActiveGeneralManagerDeletion($target)) {
            $this->haltWithMessage(__('admin.cannot_delete_last_general_manager'));
        }
    }

    public function ensureUserUpdateAllowed(User $target, array $data, ?User $actor = null): void
    {
        if ($actor && $target->is($actor) && array_key_exists('is_active', $data) && ! (bool) $data['is_active']) {
            $this->haltWithMessage(__('admin.cannot_deactivate_yourself'));
        }

        $newRoleNames = $this->resolveRoleNames($data['roles'] ?? null, $target);

        if ($actor && $target->is($actor) && $target->hasRole('general_manager') && ! in_array('general_manager', $newRoleNames, true)) {
            $this->haltWithMessage(__('admin.cannot_remove_own_general_manager_role'));
        }

        if ($this->isLastActiveGeneralManagerUpdate($target, $data, $newRoleNames)) {
            $this->haltWithMessage(__('admin.cannot_remove_last_general_manager'));
        }
    }

    public function ensureRoleDeletionAllowed(Role $role): void
    {
        if ($this->isCoreRole($role)) {
            $this->haltWithMessage(__('admin.cannot_delete_core_role'));
        }

        if ($role->users()->exists()) {
            $this->haltWithMessage(__('admin.cannot_delete_role_with_users'));
        }
    }

    public function ensureRoleUpdateAllowed(Role $role, array $data): void
    {
        if (
            $this->isCoreRole($role)
            && array_key_exists('name', $data)
            && $data['name'] !== $role->name
        ) {
            $this->haltWithMessage(__('admin.cannot_rename_core_role'));
        }
    }

    public function validateLeaveBalanceData(array $data): void
    {
        if ((float) ($data['annual_used'] ?? 0) > (float) ($data['annual_total'] ?? 0)) {
            $this->haltWithMessage(__('admin.leave_annual_used_exceeds_total'));
        }

        if ((float) ($data['sick_used'] ?? 0) > (float) ($data['sick_total'] ?? 0)) {
            $this->haltWithMessage(__('admin.leave_sick_used_exceeds_total'));
        }
    }

    public function shouldHideUserDelete(User $target, ?User $actor = null): bool
    {
        return ($actor && $target->is($actor)) || $this->isLastActiveGeneralManagerDeletion($target);
    }

    public function shouldHideRoleDelete(Role $role): bool
    {
        return $this->isCoreRole($role) || $role->users()->exists();
    }

    public function isCoreRole(Role|string $role): bool
    {
        $roleName = $role instanceof Role ? $role->name : $role;

        return in_array($roleName, self::CORE_ROLE_NAMES, true);
    }

    protected function resolveRoleNames(null|array|string $roles, User $target): array
    {
        if (is_null($roles)) {
            return $target->roles->pluck('name')->all();
        }

        $roleKeys = Collection::wrap($roles)
            ->filter(fn ($roleId) => filled($roleId))
            ->all();

        if ($roleKeys === []) {
            return [];
        }

        return Role::query()
            ->where(function ($query) use ($roleKeys): void {
                $numericRoleIds = Collection::wrap($roleKeys)
                    ->filter(fn ($roleKey) => is_numeric($roleKey))
                    ->map(fn ($roleKey) => (int) $roleKey)
                    ->all();

                $namedRoles = Collection::wrap($roleKeys)
                    ->filter(fn ($roleKey) => ! is_numeric($roleKey))
                    ->map(fn ($roleKey) => (string) $roleKey)
                    ->all();

                if ($numericRoleIds !== []) {
                    $query->whereIn('id', $numericRoleIds);
                }

                if ($namedRoles !== []) {
                    $query->orWhereIn('name', $namedRoles);
                }
            })
            ->pluck('name')
            ->all();
    }

    protected function isLastActiveGeneralManagerDeletion(User $target): bool
    {
        return $target->is_active
            && $target->hasRole('general_manager')
            && $this->countOtherActiveGeneralManagers($target) === 0;
    }

    protected function isLastActiveGeneralManagerUpdate(User $target, array $data, array $newRoleNames): bool
    {
        if (! $target->is_active || ! $target->hasRole('general_manager')) {
            return false;
        }

        $willBeActive = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $target->is_active;
        $willBeGeneralManager = in_array('general_manager', $newRoleNames, true);

        if ($willBeActive && $willBeGeneralManager) {
            return false;
        }

        return $this->countOtherActiveGeneralManagers($target) === 0;
    }

    protected function countOtherActiveGeneralManagers(User $target): int
    {
        return User::query()
            ->whereKeyNot($target->getKey())
            ->where('is_active', true)
            ->role('general_manager')
            ->count();
    }

    protected function haltWithMessage(string $message): never
    {
        Notification::make()
            ->title($message)
            ->danger()
            ->send();

        throw (new Halt)->rollBackDatabaseTransaction();
    }
}
