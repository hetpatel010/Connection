<?php

namespace App\Policies;

use App\Models\Business;
use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user, Business $business): bool
    {
        return $user->hasPermissionInBusiness('manage_roles', $business);
    }

    public function create(User $user, Business $business): bool
    {
        return $user->hasPermissionInBusiness('manage_roles', $business);
    }

    public function update(User $user, Role $role): bool
    {
        $business = business_context()->business();

        if ($business === null || $role->business_id !== $business->id || $role->is_system) {
            return false;
        }

        return $user->hasPermissionInBusiness('manage_roles', $business);
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->update($user, $role);
    }
}
