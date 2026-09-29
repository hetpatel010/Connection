<?php

namespace App\Policies;

use App\Domain\Authorization\Permissions;
use App\Models\Business;
use App\Models\User;

class BusinessPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Business $business): bool
    {
        return $user->belongsToBusiness($business)
            && $user->hasPermissionInBusiness(Permissions::MANAGE_SETTINGS, $business);
    }

    public function manageUsers(User $user, Business $business): bool
    {
        return $user->belongsToBusiness($business)
            && $user->hasPermissionInBusiness(Permissions::MANAGE_USERS, $business);
    }

    public function manageRoles(User $user, Business $business): bool
    {
        return $user->belongsToBusiness($business)
            && $user->hasPermissionInBusiness(Permissions::MANAGE_ROLES, $business);
    }

    public function exportData(User $user, Business $business): bool
    {
        return $user->belongsToBusiness($business)
            && $user->hasPermissionInBusiness(Permissions::EXPORT_DATA, $business);
    }
}
