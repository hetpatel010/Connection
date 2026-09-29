<?php

namespace App\Models;

use App\Domain\Shared\Concerns\HasUuid;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuid, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'business_user')
            ->using(BusinessUser::class)
            ->withPivot(['id', 'role_id', 'is_owner', 'joined_at'])
            ->withTimestamps();
    }

    public function belongsToBusiness(Business $business): bool
    {
        return $this->businesses()->where('businesses.id', $business->id)->exists();
    }

    public function membershipFor(Business $business): ?BusinessUser
    {
        /** @var BusinessUser|null $membership */
        $membership = $this->businesses()
            ->where('businesses.id', $business->id)
            ->first()
            ?->pivot;

        return $membership;
    }

    public function roleForBusiness(Business $business): ?Role
    {
        $membership = $this->membershipFor($business);

        if ($membership === null) {
            return null;
        }

        return Role::query()->with('permissions')->find($membership->role_id);
    }

    public function hasPermissionInBusiness(string $permission, Business $business): bool
    {
        $role = $this->roleForBusiness($business);

        if ($role === null) {
            return false;
        }

        return $role->hasPermission($permission);
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn (string $part) => Str::substr($part, 0, 1))
            ->implode('');
    }
}
