<?php

namespace App\Application\Services;

use App\Domain\Authorization\Permissions;
use App\Domain\Authorization\SimpleRolePresets;
use App\Domain\Authorization\SystemRoles;
use App\Domain\Shared\Exceptions\CannotModifySystemRoleException;
use App\Domain\Shared\Exceptions\RoleInUseException;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BusinessRoleService
{
    /**
     * @return Collection<int, Role>
     */
    public function systemRoles(): Collection
    {
        return Role::query()
            ->withCount('permissions')
            ->whereNull('business_id')
            ->where('is_system', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Role>
     */
    public function customRoles(Business $business): Collection
    {
        return Role::query()
            ->withCount('permissions')
            ->where('business_id', $business->id)
            ->where('is_system', false)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Role>
     */
    public function assignableRoles(Business $business): Collection
    {
        return Role::query()
            ->where(function ($query) use ($business): void {
                $query->whereNull('business_id')
                    ->orWhere('business_id', $business->id);
            })
            ->where('slug', '!=', SystemRoles::OWNER)
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, list<array{name: string, description: string}>>
     */
    public function permissionGroups(): array
    {
        $groups = [];

        foreach (Permissions::definitions() as $name => $definition) {
            $groups[$definition['group']][] = [
                'name' => $name,
                'description' => $definition['description'],
            ];
        }

        ksort($groups);

        return $groups;
    }

    /**
     * @param  list<string>  $permissionNames
     */
    public function create(
        Business $business,
        string $name,
        string $slug,
        ?string $description,
        array $permissionNames,
    ): Role {
        $this->assertCustomRoleSlug($business, $slug);
        $permissionIds = $this->resolvePermissionIds($permissionNames);

        return DB::transaction(function () use ($business, $name, $slug, $description, $permissionIds): Role {
            $role = Role::query()->create([
                'business_id' => $business->id,
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'is_system' => false,
            ]);

            $role->permissions()->sync($permissionIds);

            return $role->fresh(['permissions']);
        });
    }

    /**
     * @param  list<string>  $permissionNames
     */
    public function update(
        Business $business,
        Role $role,
        string $name,
        ?string $description,
        array $permissionNames,
    ): Role {
        $this->assertCustomRole($business, $role);
        $permissionIds = $this->resolvePermissionIds($permissionNames);

        return DB::transaction(function () use ($role, $name, $description, $permissionIds): Role {
            $role->update([
                'name' => $name,
                'description' => $description,
            ]);

            $role->permissions()->sync($permissionIds);

            return $role->fresh(['permissions']);
        });
    }

    public function delete(Business $business, Role $role): void
    {
        $this->assertCustomRole($business, $role);

        if (BusinessUser::query()->where('role_id', $role->id)->exists()) {
            throw RoleInUseException::forRole($role->name);
        }

        DB::transaction(function () use ($role): void {
            $role->permissions()->detach();
            $role->delete();
        });
    }

    public function resolveAssignableRole(Business $business, string $roleSlug): Role
    {
        if ($roleSlug === SystemRoles::OWNER) {
            throw CannotModifySystemRoleException::readOnly();
        }

        return Role::query()
            ->where('slug', $roleSlug)
            ->where(function ($query) use ($business): void {
                $query->whereNull('business_id')
                    ->orWhere('business_id', $business->id);
            })
            ->firstOrFail();
    }

    public function ensurePresetRole(Business $business, string $presetKey): Role
    {
        $presets = SimpleRolePresets::definitions();

        if (! array_key_exists($presetKey, $presets)) {
            throw new CannotModifySystemRoleException('Unknown role preset.');
        }

        $preset = $presets[$presetKey];
        $slug = 'simple-'.$presetKey;

        $existing = Role::query()
            ->where('business_id', $business->id)
            ->where('slug', $slug)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return $this->create(
            business: $business,
            name: $preset['name'],
            slug: $slug,
            description: $preset['description'],
            permissionNames: $preset['permissions'],
        );
    }

    public function makeSlug(Business $business, string $name, ?string $exceptRoleId = null): string
    {
        $baseSlug = Str::slug($name);

        if ($baseSlug === '') {
            $baseSlug = 'role';
        }

        $slug = $baseSlug;
        $counter = 2;

        while ($this->slugExists($business, $slug, $exceptRoleId)) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function assertCustomRole(Business $business, Role $role): void
    {
        if ($role->is_system || $role->business_id !== $business->id) {
            throw CannotModifySystemRoleException::readOnly();
        }
    }

    private function assertCustomRoleSlug(Business $business, string $slug): void
    {
        if ($slug === SystemRoles::OWNER || array_key_exists($slug, SystemRoles::definitions())) {
            throw new CannotModifySystemRoleException('This role slug is reserved for a system role.');
        }

        if ($this->slugExists($business, $slug)) {
            throw new CannotModifySystemRoleException('A role with this slug already exists.');
        }
    }

    private function slugExists(Business $business, string $slug, ?string $exceptRoleId = null): bool
    {
        return Role::query()
            ->where('slug', $slug)
            ->where(function ($query) use ($business): void {
                $query->whereNull('business_id')
                    ->orWhere('business_id', $business->id);
            })
            ->when($exceptRoleId !== null, fn ($query) => $query->where('id', '!=', $exceptRoleId))
            ->exists();
    }

    /**
     * @param  list<string>  $permissionNames
     * @return list<string>
     */
    private function resolvePermissionIds(array $permissionNames): array
    {
        $validNames = Permissions::all();

        $filtered = collect($permissionNames)
            ->filter(fn (string $name): bool => in_array($name, $validNames, true))
            ->unique()
            ->values()
            ->all();

        if ($filtered === []) {
            throw new CannotModifySystemRoleException('At least one permission is required.');
        }

        return Permission::query()
            ->whereIn('name', $filtered)
            ->pluck('id')
            ->all();
    }
}
