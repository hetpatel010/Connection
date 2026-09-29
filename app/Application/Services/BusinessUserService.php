<?php

namespace App\Application\Services;

use App\Domain\Authorization\SystemRoles;
use App\Domain\Shared\Exceptions\CannotModifyOwnerMembershipException;
use App\Domain\Shared\Exceptions\UserAlreadyMemberException;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BusinessUserService
{
    public function __construct(private BusinessRoleService $businessRoleService) {}

    public function addMember(
        Business $business,
        string $name,
        string $email,
        string $password,
        string $roleSlug,
    ): BusinessUser {
        if ($roleSlug === SystemRoles::OWNER) {
            throw CannotModifyOwnerMembershipException::cannotAssignOwner();
        }

        $role = $this->businessRoleService->resolveAssignableRole($business, $roleSlug);

        return DB::transaction(function () use ($business, $name, $email, $password, $role): BusinessUser {
            $user = User::query()->where('email', $email)->first();

            if ($user === null) {
                $user = User::query()->create([
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                ]);
            }

            if ($user->belongsToBusiness($business)) {
                throw UserAlreadyMemberException::forEmail($email);
            }

            return BusinessUser::query()->create([
                'business_id' => $business->id,
                'user_id' => $user->id,
                'role_id' => $role->id,
                'is_owner' => false,
                'joined_at' => now(),
            ]);
        });
    }

    public function updateRole(Business $business, BusinessUser $membership, string $roleSlug): BusinessUser
    {
        abort_unless($membership->business_id === $business->id, 404);

        if ($membership->is_owner) {
            throw CannotModifyOwnerMembershipException::cannotChangeRole();
        }

        if ($roleSlug === SystemRoles::OWNER) {
            throw CannotModifyOwnerMembershipException::cannotAssignOwner();
        }

        $role = $this->businessRoleService->resolveAssignableRole($business, $roleSlug);

        $membership->update(['role_id' => $role->id]);

        return $membership->fresh(['user', 'role']);
    }

    public function removeMember(Business $business, BusinessUser $membership, User $actor): void
    {
        abort_unless($membership->business_id === $business->id, 404);

        if ($membership->is_owner) {
            throw CannotModifyOwnerMembershipException::cannotRemove();
        }

        if ($membership->user_id === $actor->id) {
            throw new CannotModifyOwnerMembershipException('You cannot remove yourself from the business.');
        }

        $membership->delete();
    }

    /**
     * @return Collection<int, BusinessUser>
     */
    public function members(Business $business)
    {
        return BusinessUser::query()
            ->with(['user', 'role'])
            ->where('business_id', $business->id)
            ->orderByDesc('is_owner')
            ->orderBy('joined_at')
            ->get();
    }

    /**
     * @return Collection<int, Role>
     */
    public function assignableRoles(Business $business)
    {
        return $this->businessRoleService->assignableRoles($business);
    }
}
