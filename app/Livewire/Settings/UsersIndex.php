<?php

namespace App\Livewire\Settings;

use App\Application\Services\BusinessUserService;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\BusinessUser;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class UsersIndex extends Component
{
    use DispatchesToasts;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $roleSlug = 'salesperson';

    public function mount(): void
    {
        $business = business_context()->business();
        abort_if($business === null, 404);

        $this->authorize('manageUsers', $business);
    }

    public function invite(BusinessUserService $businessUserService): void
    {
        $business = business_context()->business();
        abort_if($business === null, 404);

        $this->authorize('manageUsers', $business);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'roleSlug' => ['required', 'string'],
        ]);

        $businessUserService->addMember(
            business: $business,
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
            roleSlug: $validated['roleSlug'],
        );

        $this->reset(['name', 'email', 'password']);
        $this->roleSlug = 'salesperson';

        $this->toastSuccess(__('User invited to this business.'));
    }

    public function updateRole(string $membershipId, string $roleSlug, BusinessUserService $businessUserService): void
    {
        $business = business_context()->business();
        abort_if($business === null, 404);

        $this->authorize('manageUsers', $business);

        $membership = BusinessUser::query()->findOrFail($membershipId);
        $businessUserService->updateRole($business, $membership, $roleSlug);

        $this->toastSuccess(__('User role updated.'));
    }

    public function removeMember(string $membershipId, BusinessUserService $businessUserService): void
    {
        $business = business_context()->business();
        abort_if($business === null, 404);

        $this->authorize('manageUsers', $business);

        $membership = BusinessUser::query()->findOrFail($membershipId);
        $businessUserService->removeMember($business, $membership, auth()->user());

        $this->toastSuccess(__('User removed from this business.'));
    }

    public function render(BusinessUserService $businessUserService): View
    {
        $business = business_context()->business();

        return view('livewire.settings.users-index', [
            'members' => $business ? $businessUserService->members($business) : collect(),
            'assignableRoles' => $business ? $businessUserService->assignableRoles($business) : collect(),
        ]);
    }
}
