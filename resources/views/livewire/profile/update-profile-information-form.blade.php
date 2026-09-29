<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
        $this->dispatch('toast', message: __('Saved.'), type: 'success');
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        $message = __('A new verification link has been sent.');
        Session::flash('status', $message);
        $this->dispatch('toast', message: $message, type: 'success');
    }
}; ?>

<x-settings-section
    :title="__('Profile Information')"
    :description="__('Update your account\'s profile information and email address.')"
>
    <form wire:submit="updateProfileInformation" data-shortcut-form class="max-w-lg space-y-5">
        <x-form-field :label="__('Name')" for="name">
            <x-text-input wire:model="name" id="name" name="name" type="text" class="block w-full" required autofocus autocomplete="name" />
            <x-slot:error>
                <x-input-error class="mt-1.5" :messages="$errors->get('name')" />
            </x-slot:error>
        </x-form-field>

        <x-form-field :label="__('Email')" for="email">
            <x-text-input wire:model="email" id="email" name="email" type="email" class="block w-full" required autocomplete="username" />
            <x-slot:error>
                <x-input-error class="mt-1.5" :messages="$errors->get('email')" />
            </x-slot:error>

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-900">
                    <p>{{ __('Your email address is unverified.') }}</p>
                    <button type="button" wire:click.prevent="sendVerification" class="mt-1 font-medium text-indigo-600 hover:text-indigo-500">
                        {{ __('Re-send verification email') }}
                    </button>
                </div>
            @endif
        </x-form-field>

        <x-form-actions class="pt-1">
            <x-primary-button data-shortcut-save>{{ __('Save') }}</x-primary-button>
            <x-action-message on="profile-updated">{{ __('Saved.') }}</x-action-message>
        </x-form-actions>
    </form>
</x-settings-section>
