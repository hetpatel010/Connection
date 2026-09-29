<div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('accounting.accounts.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 transition hover:text-slate-900">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            {{ __('Back to Chart of Accounts') }}
        </a>

        <div class="flex flex-wrap items-center gap-2">
            @can('update', $account)
                <button
                    type="button"
                    wire:click="toggleStatus"
                    wire:confirm="{{ $account->status->value === 'active' ? __('Deactivate this account? It will no longer be selectable for new transactions.') : __('Activate this account?') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    {{ $account->status->value === 'active' ? __('Deactivate') : __('Activate') }}
                </button>
                <x-link-button href="{{ route('accounting.accounts.edit', $account) }}" wire:navigate>{{ __('Edit account') }}</x-link-button>
            @endcan
            @can('delete', $account)
                <button
                    type="button"
                    wire:click="delete"
                    wire:confirm="{{ __('Delete this account? This cannot be undone.') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 shadow-sm transition hover:bg-red-50"
                >
                    {{ __('Delete') }}
                </button>
            @endcan
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-card flush>
                <x-slot:header>
                    <h2 class="text-base font-semibold text-slate-900">{{ __('Account details') }}</h2>
                </x-slot:header>

                <dl class="grid gap-x-6 gap-y-5 p-5 sm:grid-cols-2 sm:p-6">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Account code') }}</dt>
                        <dd class="mt-1 font-mono text-sm text-slate-900">{{ $account->code }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Account name') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $account->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Type') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            {{ $account->type->label() }}
                            @if ($locked['type'])
                                <span class="ml-1.5 text-xs text-slate-500">{{ __('(locked)') }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Group') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $account->group }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Parent account') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">
                            @if ($account->parent)
                                <a href="{{ route('accounting.accounts.show', $account->parent) }}" wire:navigate class="hover:text-indigo-600">{{ $account->parent->code }} — {{ $account->parent->name }}</a>
                            @else
                                <span class="text-slate-500">{{ __('Group header') }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            <x-status-badge :status="$account->status->value" />
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Opening balance') }}</dt>
                        <dd class="mt-1 font-mono text-sm text-slate-900">
                            @if ($account->opening_balance > 0)
                                {{ \App\Support\Money::format($account->opening_balance) }} {{ strtoupper($account->opening_balance_side?->value ?? $account->type->normalBalanceSide()->value) }}
                            @else
                                <span class="text-slate-500">—</span>
                            @endif
                            @if ($locked['opening_balance'])
                                <span class="ml-1.5 text-xs text-slate-500">{{ __('(locked)') }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Current balance') }}</dt>
                        <dd class="mt-1 font-mono text-sm text-slate-900">
                            @if ($balance !== null)
                                {{ \App\Support\Money::format($balance['amount']) }} {{ strtoupper($balance['side']->value) }}
                            @else
                                <span class="text-slate-500">{{ __('No financial year selected') }}</span>
                            @endif
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Description / notes') }}</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $account->description ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Created') }}</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $account->created_at?->format('d M Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Last updated') }}</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $account->updated_at?->format('d M Y H:i') }}</dd>
                    </div>
                </dl>
            </x-card>
        </div>

        <div class="space-y-5">
            <x-card flush>
                <x-slot:header>
                    <h2 class="text-base font-semibold text-slate-900">{{ __('Usage') }}</h2>
                </x-slot:header>

                <div class="p-5 sm:p-6">
                    @if ($usage === [])
                        <x-alert type="success">
                            {{ __('This account is not used in any accounting record yet, so it can be safely deleted.') }}
                        </x-alert>
                    @else
                        <x-alert type="warning">
                            {{ __('This account is already used in accounting records. It cannot be deleted — deactivate it instead to stop it being used in new transactions.') }}
                        </x-alert>

                        <dl class="mt-4 space-y-2">
                            @foreach ($usage as $source => $count)
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <dt class="text-slate-600">{{ \Illuminate\Support\Str::headline($source) }}</dt>
                                    <dd class="font-mono font-medium text-slate-900">{{ $count }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </div>
            </x-card>

            @if ($children->isNotEmpty())
                <x-card flush>
                    <x-slot:header>
                        <h2 class="text-base font-semibold text-slate-900">{{ __('Sub accounts') }}</h2>
                    </x-slot:header>

                    <ul class="divide-y divide-slate-100" role="list">
                        @foreach ($children as $child)
                            <li class="px-5 py-3 sm:px-6">
                                <div class="flex items-center justify-between gap-3">
                                    <a href="{{ route('accounting.accounts.show', $child) }}" wire:navigate class="min-w-0 text-sm font-medium text-slate-900 hover:text-indigo-600">
                                        <span class="font-mono text-xs text-slate-500">{{ $child->code }}</span>
                                        <span class="block truncate">{{ $child->name }}</span>
                                    </a>
                                    <x-status-badge :status="$child->status->value" />
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif

            @if ($account->isPostable())
                <x-card flush>
                    <x-slot:header>
                        <h2 class="text-base font-semibold text-slate-900">{{ __('Transactions') }}</h2>
                    </x-slot:header>

                    <div class="p-5 sm:p-6">
                        <a href="{{ route('accounting.ledger') }}" wire:navigate class="text-sm font-medium text-indigo-600 transition hover:text-indigo-500">
                            {{ __('View in ledger report') }}
                        </a>
                    </div>
                </x-card>
            @endif
        </div>
    </div>
</div>
