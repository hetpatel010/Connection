<?php

namespace Database\Seeders;

use App\Domain\Authorization\Permissions;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Permissions::definitions() as $name => $definition) {
            Permission::query()->updateOrCreate(
                ['name' => $name],
                [
                    'group' => $definition['group'],
                    'description' => $definition['description'],
                ],
            );
        }
    }
}
