<div>
    <x-index-toolbar
        export-module="accounts"
        :export-search="$search"
        :export-type="$type"
        :export-group="$group"
        :export-status="$status"
        :export-view="$view"
    >
        <x-slot:search>
            <x-search-input wire:model.live.debounce.300ms="search" :placeholder="__('Search by code, name, type or group...')" />
        </x-slot:search>

        <x-slot:actions>
            <x-link-button href="{{ route('accounting.accounts.create') }}" wire:navigate>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('Add Account') }}
            </x-link-button>
        </x-slot:actions>
    </x-index-toolbar>

    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label for="account-filter-view" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Show') }}</label>
            <x-select-input wire:model.live="view" id="account-filter-view">
                <option value="all">{{ __('All accounts') }}</option>
                <option value="postable">{{ __('Postable accounts only') }}</option>
                <option value="groups">{{ __('Group headers only') }}</option>
            </x-select-input>
        </div>
        <div>
            <label for="account-filter-type" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Type') }}</label>
            <x-select-input wire:model.live="type" id="account-filter-type">
                <option value="">{{ __('All types') }}</option>
                @foreach ($accountTypes as $accountType)
                    <option value="{{ $accountType->value }}">{{ $accountType->label() }}</option>
                @endforeach
            </x-select-input>
        </div>
        <div>
            <label for="account-filter-group" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Group') }}</label>
            <x-select-input wire:model.live="group" id="account-filter-group">
                <option value="">{{ __('All groups') }}</option>
                @foreach ($groupOptions as $groupOption)
                    <option value="{{ $groupOption }}">{{ $groupOption }}</option>
                @endforeach
            </x-select-input>
        </div>
        <div>
            <label for="account-filter-status" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</label>
            <x-select-input wire:model.live="status" id="account-filter-status">
                <option value="">{{ __('All statuses') }}</option>
                @foreach ($accountStatuses as $accountStatus)
                    <option value="{{ $accountStatus->value }}">{{ ucfirst($accountStatus->value) }}</option>
                @endforeach
            </x-select-input>
        </div>
    </div>

    @if ($search !== '' || $type !== '' || $group !== '' || $status !== '' || $view !== 'all')
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <p class="text-sm text-slate-500">{{ __('Filters applied') }}</p>
            <button type="button" wire:click="resetFilters" class="text-sm font-medium text-indigo-600 transition hover:text-indigo-500">
                {{ __('Clear all filters') }}
            </button>
        </div>
    @endif

    <x-card flush>
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="table-head-cell">{{ __('Code') }}</th>
                        <th class="table-head-cell">{{ __('Name') }}</th>
                        <th class="table-head-cell">{{ __('Type') }}</th>
                        <th class="table-head-cell">{{ __('Group') }}</th>
                        <th class="table-head-cell">{{ __('Status') }}</th>
                        <th class="table-head-cell text-right"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($accounts as $account)
                        <tr wire:key="account-{{ $account->id }}" class="table-row">
                            <td class="table-cell font-mono font-medium text-slate-900">{{ $account->code }}</td>
                            <td class="table-cell text-slate-900">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ route('accounting.accounts.show', $account) }}" wire:navigate class="font-medium hover:text-indigo-600">{{ $account->name }}</a>
                                    @if ($account->isGroupHeader())
                                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-600">{{ __('Group header') }}</span>
                                    @endif
                                    @if ($account->journal_lines_count > 0)
                                        <span class="rounded bg-indigo-50 px-1.5 py-0.5 text-[11px] font-medium text-indigo-700">{{ __('In use') }}</span>
                                    @endif
                                </div>
                                @if ($account->description)
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $account->description }}</p>
                                @endif
                            </td>
                            <td class="table-cell text-slate-600">{{ $account->type->label() }}</td>
                            <td class="table-cell text-slate-600">{{ $account->group }}</td>
                            <td class="table-cell">
                                <x-status-badge :status="$account->status->value" />
                            </td>
                            <td class="table-cell text-right">
                                <div class="flex flex-wrap justify-end items-center gap-3">
                                    @can('view', $account)
                                        <x-table-link href="{{ route('accounting.accounts.show', $account) }}" wire:navigate>{{ __('View') }}</x-table-link>
                                    @endcan
                                    @can('update', $account)
                                        <x-table-link href="{{ route('accounting.accounts.edit', $account) }}" wire:navigate>{{ __('Edit') }}</x-table-link>
                                        <button type="button" wire:click="toggleStatus('{{ $account->id }}')" class="text-sm font-medium text-amber-600 transition hover:text-amber-500">
                                            {{ $account->status->value === 'active' ? __('Deactivate') : __('Activate') }}
                                        </button>
                                    @endcan
                                    @can('delete', $account)
                                        <button type="button" wire:click="deleteAccount('{{ $account->id }}')" wire:confirm="{{ __('Delete this account? This cannot be undone.') }}" class="text-sm font-medium text-red-600 transition hover:text-red-500">{{ __('Delete') }}</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state
                            :title="__('No accounts found.')"
                            :description="__('Try adjusting your search or filters, or add a new account.')"
                            :colspan="6"
                        />
                    @endforelse
                </tbody>
            </table>
        </div>

        <ul class="divide-y divide-slate-100 md:hidden" role="list">
            @forelse ($accounts as $account)
                <li wire:key="account-mobile-{{ $account->id }}" class="px-4 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs font-medium text-slate-500">{{ $account->code }}</span>
                                <x-status-badge :status="$account->status->value" />
                                @if ($account->isGroupHeader())
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-medium text-slate-600">{{ __('Group header') }}</span>
                                @endif
                            </div>
                            <a href="{{ route('accounting.accounts.show', $account) }}" wire:navigate class="mt-1 block truncate text-sm font-medium text-slate-900 hover:text-indigo-600">{{ $account->name }}</a>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $account->type->label() }} &middot; {{ $account->group }}</p>
                        </div>

                        <x-dropdown align="right" width="48">
                            <x-slot:trigger>
                                <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                    {{ __('Actions') }}
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>
                            </x-slot:trigger>

                            <x-slot:content>
                                <div class="py-1">
                                    @can('view', $account)
                                        <x-dropdown-link href="{{ route('accounting.accounts.show', $account) }}" wire:navigate>{{ __('View details') }}</x-dropdown-link>
                                    @endcan
                                    @can('update', $account)
                                        <x-dropdown-link href="{{ route('accounting.accounts.edit', $account) }}" wire:navigate>{{ __('Edit account') }}</x-dropdown-link>
                                        <x-dropdown-link href="#" wire:click="toggleStatus('{{ $account->id }}')">
                                            {{ $account->status->value === 'active' ? __('Deactivate') : __('Activate') }}
                                        </x-dropdown-link>
                                    @endcan
                                    @can('delete', $account)
                                        <x-dropdown-link href="#" wire:click="deleteAccount('{{ $account->id }}')" wire:confirm="{{ __('Delete this account? This cannot be undone.') }}" class="text-red-600 hover:bg-red-50">
                                            {{ __('Delete account') }}
                                        </x-dropdown-link>
                                    @endcan
                                </div>
                            </x-slot:content>
                        </x-dropdown>
                    </div>
                </li>
            @empty
                <li class="px-4 py-10 text-center">
                    <p class="text-sm font-medium text-slate-900">{{ __('No accounts found.') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Try adjusting your search or filters, or add a new account.') }}</p>
                    <x-link-button href="{{ route('accounting.accounts.create') }}" wire:navigate class="mt-4">{{ __('Add Account') }}</x-link-button>
                </li>
            @endforelse
        </ul>
    </x-card>

    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-500">
            {{ __('Showing :count accounts', ['count' => $accounts->total()]) }}
        </p>
        <div>{{ $accounts->links() }}</div>
    </div>
</div>
