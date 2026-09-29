<?php

namespace App\Livewire\Settings;

use App\Application\Services\BusinessRoleService;
use App\Domain\Authorization\SimpleRolePresets;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\Role;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class RolesIndex extends Component
{
    use DispatchesToasts;

    public ?string $editingRoleId = null;

    public string $name = '';

    public string $description = '';

    /** @var list<string> */
    public array $selectedPermissions = [];

    public function mount(): void
    {
        $business = business_context()->business();
        abort_if($business === null, 404);

        $this->authorize('manageRoles', $business);
    }

    public function startCreate(): void
    {
        $business = business_context()->business();
        abort_if($business === null, 404);

        $this->authorize('create', [Role::class, $business]);

        $this->resetForm();
        $this->editingRoleId = 'new';
    }

    public function startEdit(string $roleId): void
    {
        $role = Role::query()->findOrFail($roleId);
        $this->authorize('update', $role);

        $this->editingRoleId = $role->id;
        $this->name = $role->name;
        $this->description = $role->description ?? '';
        $this->selectedPermissions = $role->permissions()->pluck('name')->all();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function save(BusinessRoleService $businessRoleService): void
    {
        $business = business_context()->business();
        abort_if($business === null, 404);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'selectedPermissions' => ['required', 'array', 'min:1'],
            'selectedPermissions.*' => ['string'],
        ]);

        if ($this->editingRoleId === 'new') {
            $this->authorize('create', [Role::class, $business]);

            $businessRoleService->create(
                business: $business,
                name: $validated['name'],
                slug: $businessRoleService->makeSlug($business, $validated['name']),
                description: $validated['description'] ?: null,
                permissionNames: $validated['selectedPermissions'],
            );

            $this->toastSuccess(__('Custom role created.'));
        } else {
            $role = Role::query()->findOrFail($this->editingRoleId);
            $this->authorize('update', $role);

            $businessRoleService->update(
                business: $business,
                role: $role,
                name: $validated['name'],
                description: $validated['description'] ?: null,
                permissionNames: $validated['selectedPermissions'],
            );

            $this->toastSuccess(__('Custom role updated.'));
        }

        $this->resetForm();
    }

    public function deleteRole(string $roleId, BusinessRoleService $businessRoleService): void
    {
        $business = business_context()->business();
        abort_if($business === null, 404);

        $role = Role::query()->findOrFail($roleId);
        $this->authorize('delete', $role);

        $businessRoleService->delete($business, $role);

        if ($this->editingRoleId === $roleId) {
            $this->resetForm();
        }

        $this->toastSuccess(__('Custom role deleted.'));
    }

    public function applyPreset(string $presetKey, BusinessRoleService $businessRoleService): void
    {
        $business = business_context()->business();
        abort_if($business === null, 404);

        $this->authorize('manageRoles', $business);

        $role = $businessRoleService->ensurePresetRole($business, $presetKey);

        $this->toastSuccess(__('Role “:name” is ready to assign to team members.', ['name' => $role->name]));
    }

    public function render(BusinessRoleService $businessRoleService): View
    {
        $business = business_context()->business();

        return view('livewire.settings.roles-index', [
            'systemRoles' => $businessRoleService->systemRoles(),
            'customRoles' => $business ? $businessRoleService->customRoles($business) : collect(),
            'permissionGroups' => $businessRoleService->permissionGroups(),
            'rolePresets' => SimpleRolePresets::definitions(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['editingRoleId', 'name', 'description', 'selectedPermissions']);
    }
}
