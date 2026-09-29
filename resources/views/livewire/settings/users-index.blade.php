<div>
    <div class="mb-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 font-semibold text-slate-900">{{ __('Invite user') }}</div>
        <form wire:submit="invite" class="grid gap-4 p-4 md:grid-cols-2">
            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input wire:model="email" id="email" type="email" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="password" :value="__('Temporary password')" />
                <x-text-input wire:model="password" id="password" type="password" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="roleSlug" :value="__('Role')" />
                <x-select-input wire:model="roleSlug" id="roleSlug" class="mt-1 block w-full">
                    @foreach ($assignableRoles as $role)
                        <option value="{{ $role->slug }}">{{ $role->name }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div class="md:col-span-2 flex justify-end">
                <x-primary-button>{{ __('Add user') }}</x-primary-button>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Email') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Role') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Joined') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($members as $membership)
                    <tr wire:key="member-{{ $membership->id }}">
                        <td class="px-4 py-3 text-slate-900">
                            {{ $membership->user->name }}
                            @if ($membership->is_owner)
                                <span class="ml-2 rounded bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800">{{ __('Owner') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $membership->user->email }}</td>
                        <td class="px-4 py-3 text-slate-900">
                            @if ($membership->is_owner)
                                {{ $membership->role->name }}
                            @else
                                <x-select-input
                                    wire:change="updateRole('{{ $membership->id }}', $event.target.value)"
                                >
                                    @foreach ($assignableRoles as $role)
                                        <option value="{{ $role->slug }}" @selected($membership->role->slug === $role->slug)>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </x-select-input>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $membership->joined_at?->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if (! $membership->is_owner && $membership->user_id !== auth()->id())
                                <button type="button" wire:click="removeMember('{{ $membership->id }}')"
                                    wire:confirm="{{ __('Remove this user from the business?') }}"
                                    class="text-sm text-red-600 hover:text-red-800">
                                    {{ __('Remove') }}
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ __('No team members found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
