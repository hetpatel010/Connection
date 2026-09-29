<div>
    <div class="mb-6 flex justify-end">
        @if ($editingRoleId === null)
            <button type="button" wire:click="startCreate"
                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                {{ __('New custom role') }}
            </button>
        @endif
    </div>

    @if ($editingRoleId !== null)
        <div class="mb-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 font-semibold text-slate-900">
                {{ $editingRoleId === 'new' ? __('Create custom role') : __('Edit custom role') }}
            </div>
            <form wire:submit="save" class="space-y-6 p-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="roleName" :value="__('Name')" />
                        <x-text-input wire:model="name" id="roleName" type="text" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="roleDescription" :value="__('Description')" />
                        <x-text-input wire:model="description" id="roleDescription" type="text" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <p class="mb-3 text-sm font-medium text-slate-900">{{ __('Permissions') }}</p>
                    <x-input-error :messages="$errors->get('selectedPermissions')" class="mb-3" />
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($permissionGroups as $group => $permissions)
                            <div class="rounded-md border border-slate-200 p-3">
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ ucfirst($group) }}</p>
                                <div class="space-y-2">
                                    @foreach ($permissions as $permission)
                                        <label class="flex items-start gap-2 text-sm text-slate-700">
                                            <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission['name'] }}"
                                                class="mt-0.5 rounded border-slate-300 text-indigo-600 shadow-sm" />
                                            <span>{{ $permission['description'] }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="cancelEdit"
                        class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        {{ __('Cancel') }}
                    </button>
                    <x-primary-button>{{ __('Save role') }}</x-primary-button>
                </div>
            </form>
        </div>
    @endif

    <div class="mb-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 font-semibold text-slate-900">{{ __('Quick role presets') }}</div>
        <div class="grid gap-3 p-4 md:grid-cols-3">
            @foreach ($rolePresets as $key => $preset)
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="font-medium text-slate-900">{{ $preset['name'] }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $preset['description'] }}</p>
                    <button type="button" wire:click="applyPreset('{{ $key }}')"
                        class="mt-3 text-sm font-medium text-indigo-600 hover:text-indigo-800">
                        {{ __('Create role') }}
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mb-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 font-semibold text-slate-900">{{ __('Custom roles') }}</div>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Description') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Permissions') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($customRoles as $role)
                    <tr wire:key="custom-role-{{ $role->id }}">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $role->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $role->description ?: '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $role->permissions_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <button type="button" wire:click="startEdit('{{ $role->id }}')" class="text-indigo-600 hover:text-indigo-800">{{ __('Edit') }}</button>
                                <button type="button" wire:click="deleteRole('{{ $role->id }}')"
                                    wire:confirm="{{ __('Delete this custom role?') }}"
                                    class="text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">{{ __('No custom roles yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 font-semibold text-slate-900">{{ __('System roles (read-only)') }}</div>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Description') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Permissions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($systemRoles as $role)
                    <tr wire:key="system-role-{{ $role->id }}">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $role->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $role->description }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $role->permissions_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
