<?php

use App\Application\Services\BusinessContext;
use App\Application\Services\BusinessService;
use App\Application\Services\OnboardingSampleDataService;
use App\Models\Business;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] #[Title('Set up your business')] class extends Component
{
    public int $step = 1;

    public string $name = '';

    public string $state = 'Gujarat';

    public string $gstin = '';

    public string $phone = '';

    public string $city = '';

    public string $addressLine1 = '';

    public bool $loadSampleData = true;

    public ?string $businessId = null;

    public function mount(BusinessContext $businessContext): void
    {
        if ($businessContext->hasBusiness()) {
            $this->redirectRoute('dashboard', navigate: true);
        }
    }

    public function nextStep(BusinessService $businessService, BusinessContext $businessContext): void
    {
        if ($this->step === 1) {
            $validated = $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'state' => ['required', 'string', 'max:100'],
            ]);

            if ($this->businessId !== null) {
                $business = $this->currentBusiness();
                app(BusinessService::class)->updateProfile($business, [
                    'name' => $validated['name'],
                    'state' => $validated['state'],
                ]);
            } else {
                $business = $businessService->createForOwner(auth()->user(), $validated);
                $this->businessId = $business->id;
                $businessContext->setBusiness($business, auth()->user());
            }

            $this->step = 2;

            return;
        }

        if ($this->step === 2) {
            $this->validate([
                'gstin' => ['nullable', 'string', 'size:15'],
                'phone' => ['nullable', 'string', 'max:30'],
                'city' => ['nullable', 'string', 'max:100'],
                'addressLine1' => ['nullable', 'string', 'max:255'],
            ]);

            $business = $this->currentBusiness();
            app(BusinessService::class)->updateProfile($business, [
                'name' => $business->name,
                'gstin' => $this->gstin ?: null,
                'phone' => $this->phone ?: null,
                'city' => $this->city ?: null,
                'address_line_1' => $this->addressLine1 ?: null,
                'state' => $business->state,
            ]);

            $this->step = 3;
        }
    }

    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function finish(OnboardingSampleDataService $sampleDataService, BusinessContext $businessContext): void
    {
        $business = $this->currentBusiness();

        if ($this->loadSampleData) {
            $sampleDataService->seedIfEmpty($business, auth()->user());
        }

        $businessContext->setBusiness($business->fresh(), auth()->user());

        $this->redirectRoute('dashboard', navigate: true);
    }

    private function currentBusiness(): Business
    {
        return Business::query()->findOrFail($this->businessId);
    }
}; ?>

<div class="w-full sm:max-w-lg mt-6 px-6 py-6 bg-white shadow-md overflow-hidden sm:rounded-xl">
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">{{ __('Step :current of 3', ['current' => $step]) }}</p>
        <h2 class="mt-1 text-xl font-semibold text-gray-900">
            @if ($step === 1)
                {{ __('Create your business') }}
            @elseif ($step === 2)
                {{ __('Business details') }}
            @else
                {{ __('You\'re almost ready') }}
            @endif
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            @if ($step === 1)
                {{ __('Start with your business name and state for GST compliance.') }}
            @elseif ($step === 2)
                {{ __('Add GSTIN and contact details — you can skip and fill these later in settings.') }}
            @else
                {{ __('Optionally load sample customers, products, and invoices to explore the app.') }}
            @endif
        </p>
    </div>

    <div class="mb-6 flex gap-2">
        @foreach ([1, 2, 3] as $index)
            <div class="h-1.5 flex-1 rounded-full {{ $step >= $index ? 'bg-indigo-600' : 'bg-slate-200' }}"></div>
        @endforeach
    </div>

    @if ($step === 1)
        <form wire:submit="nextStep" class="space-y-4">
            <div>
                <x-input-label for="name" :value="__('Business name')" />
                <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="state" :value="__('State (Place of supply)')" />
                <x-text-input wire:model="state" id="state" class="block mt-1 w-full" type="text" required />
                <x-input-error :messages="$errors->get('state')" class="mt-2" />
            </div>
            <x-primary-button class="w-full justify-center">{{ __('Continue') }}</x-primary-button>
        </form>
    @elseif ($step === 2)
        <form wire:submit="nextStep" class="space-y-4">
            <div>
                <x-input-label for="gstin" :value="__('GSTIN (optional)')" />
                <x-text-input wire:model="gstin" id="gstin" class="block mt-1 w-full font-mono uppercase" maxlength="15" />
                <x-input-error :messages="$errors->get('gstin')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="phone" :value="__('Phone (optional)')" />
                <x-text-input wire:model="phone" id="phone" class="block mt-1 w-full" type="tel" />
                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="city" :value="__('City (optional)')" />
                <x-text-input wire:model="city" id="city" class="block mt-1 w-full" />
            </div>
            <div>
                <x-input-label for="addressLine1" :value="__('Address (optional)')" />
                <x-text-input wire:model="addressLine1" id="addressLine1" class="block mt-1 w-full" />
            </div>
            <div class="flex gap-3">
                <button type="button" wire:click="previousStep" class="flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</button>
                <x-primary-button class="flex-1 justify-center">{{ __('Continue') }}</x-primary-button>
            </div>
        </form>
    @else
        <div class="space-y-5">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <h3 class="text-sm font-semibold text-slate-900">{{ __('Setup checklist') }}</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li class="flex items-center gap-2"><span class="text-emerald-600">✓</span> {{ __('Business profile created') }}</li>
                    <li class="flex items-center gap-2"><span class="text-emerald-600">✓</span> {{ __('Chart of accounts & financial year ready') }}</li>
                    <li class="flex items-center gap-2"><span class="text-slate-400">○</span> {{ __('Add customers & products (or load samples below)') }}</li>
                </ul>
            </div>
            <label class="flex items-start gap-3 rounded-lg border border-indigo-100 bg-indigo-50/50 p-4">
                <input wire:model="loadSampleData" type="checkbox" class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20" />
                <span>
                    <span class="block text-sm font-medium text-slate-900">{{ __('Load sample data') }}</span>
                    <span class="mt-0.5 block text-sm text-slate-600">{{ __('Customers, products, sample invoices, and expenses to try the app immediately.') }}</span>
                </span>
            </label>
            <div class="flex gap-3">
                <button type="button" wire:click="previousStep" class="flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">{{ __('Back') }}</button>
                <button type="button" wire:click="finish" class="flex-1 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('Go to dashboard') }}</button>
            </div>
        </div>
    @endif
</div>
