<?php

namespace App\Livewire\Business;

use App\Application\Services\BusinessContext;
use App\Application\Services\BusinessService;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\Business;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreateBusiness extends Component
{
    use DispatchesToasts;

    public string $name = '';

    public string $state = 'Gujarat';

    public string $gstin = '';

    public function mount(): void
    {
        $this->authorize('create', Business::class);
    }

    public function save(BusinessService $businessService, BusinessContext $businessContext): void
    {
        $this->authorize('create', Business::class);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:100'],
            'gstin' => ['nullable', 'string', 'size:15'],
        ]);

        $business = $businessService->createForOwner(auth()->user(), [
            'name' => $validated['name'],
            'state' => $validated['state'],
            'legal_name' => $validated['name'],
        ]);

        if ($validated['gstin']) {
            $businessService->updateProfile($business, [
                'name' => $business->name,
                'gstin' => $validated['gstin'],
                'state' => $validated['state'],
            ]);
        }

        $businessContext->setBusiness($business->fresh(), auth()->user());

        $this->toastSuccess(__('Shop “:name” created. You are now working in this business.', ['name' => $business->name]));

        $this->redirectRoute('dashboard', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.business.create-business');
    }
}
