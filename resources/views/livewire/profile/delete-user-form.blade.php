<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<x-settings-section
    :title="__('Delete Account')"
    :description="__('Once deleted, all account data is permanently removed. Download anything you need to keep first.')"
    danger
>
    <x-danger-button
        type="button"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Delete Account') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <x-slot:header>
            <h2 class="text-lg font-semibold text-slate-900">
                {{ __('Are you sure you want to delete your account?') }}
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('Please enter your password to confirm permanent deletion.') }}
            </p>
        </x-slot:header>

        <form wire:submit="deleteUser" id="delete-user-form" data-shortcut-form>
            <x-form-field :label="__('Password')" for="password">
                <x-text-input
                    wire:model="password"
                    id="password"
                    name="password"
                    type="password"
                    class="block w-full"
                    placeholder="{{ __('Your password') }}"
                />
                <x-slot:error>
                    <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
                </x-slot:error>
            </x-form-field>
        </form>

        <x-slot:footer>
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button form="delete-user-form" data-shortcut-save>
                {{ __('Delete Account') }}
            </x-danger-button>
        </x-slot:footer>
    </x-modal>
</x-settings-section>
