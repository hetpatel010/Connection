<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
        $this->dispatch('toast', message: __('Saved.'), type: 'success');
    }
}; ?>

<x-settings-section
    :title="__('Update Password')"
    :description="__('Use a long, random password to keep your account secure.')"
>
    <form wire:submit="updatePassword" data-shortcut-form class="max-w-lg space-y-5">
        <x-form-field :label="__('Current password')" for="update_password_current_password">
            <x-text-input wire:model="current_password" id="update_password_current_password" name="current_password" type="password" class="block w-full" autocomplete="current-password" />
            <x-slot:error>
                <x-input-error class="mt-1.5" :messages="$errors->get('current_password')" />
            </x-slot:error>
        </x-form-field>

        <x-form-field :label="__('New password')" for="update_password_password">
            <x-text-input wire:model="password" id="update_password_password" name="password" type="password" class="block w-full" autocomplete="new-password" />
            <x-slot:error>
                <x-input-error class="mt-1.5" :messages="$errors->get('password')" />
            </x-slot:error>
        </x-form-field>

        <x-form-field :label="__('Confirm password')" for="update_password_password_confirmation">
            <x-text-input wire:model="password_confirmation" id="update_password_password_confirmation" name="password_confirmation" type="password" class="block w-full" autocomplete="new-password" />
            <x-slot:error>
                <x-input-error class="mt-1.5" :messages="$errors->get('password_confirmation')" />
            </x-slot:error>
        </x-form-field>

        <x-form-actions class="pt-1">
            <x-primary-button data-shortcut-save>{{ __('Save') }}</x-primary-button>
            <x-action-message on="password-updated">{{ __('Saved.') }}</x-action-message>
        </x-form-actions>
    </form>
</x-settings-section>
