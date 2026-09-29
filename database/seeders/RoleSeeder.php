<?php

namespace Database\Seeders;

use App\Domain\Authorization\SystemRoles;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = Permission::query()->get()->keyBy('name');

        foreach (SystemRoles::definitions() as $slug => $definition) {
            $role = Role::query()->updateOrCreate(
                [
                    'business_id' => null,
                    'slug' => $slug,
                ],
                [
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'is_system' => true,
                ],
            );

            $permissionIds = collect($definition['permissions'])
                ->map(fn (string $name) => $permissions[$name]->id ?? null)
                ->filter()
                ->values()
                ->all();

            $role->permissions()->sync($permissionIds);
        }
    }
}
