<div
    x-data="{ deleteAction: '' }"
    x-on:customer-delete-confirm="deleteAction = $event.detail.url"
>
    <x-index-toolbar export-module="customers" :export-search="$search">
        <x-slot:search>
            <x-search-input wire:model.live.debounce.300ms="search" :placeholder="__('Search customers...')" />
        </x-slot:search>
        <x-slot:actions>
            @can('manage_customers')
                <x-link-button href="{{ route('masters.customers.create') }}" wire:navigate>
                    {{ __('Add Customer') }}
                </x-link-button>
            @endcan
        </x-slot:actions>
    </x-index-toolbar>

    <x-card flush>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="table-head-cell">{{ __('Name') }}</th>
                        <th class="table-head-cell">{{ __('Phone') }}</th>
                        <th class="table-head-cell">{{ __('GSTIN') }}</th>
                        <th class="table-head-cell">{{ __('State') }}</th>
                        <th class="table-head-cell">{{ __('Status') }}</th>
                        <th class="table-head-cell"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($customers as $customer)
                        <tr wire:key="customer-{{ $customer->id }}" class="table-row">
                            <td class="table-cell font-medium text-slate-900">{{ $customer->name }}</td>
                            <td class="table-cell text-slate-600">{{ $customer->phone ?? '—' }}</td>
                            <td class="table-cell font-mono text-slate-600">{{ $customer->gstin ?? '—' }}</td>
                            <td class="table-cell text-slate-600">{{ $customer->billing_state ?? '—' }}</td>
                            <td class="table-cell">
                                <x-status-badge :status="$customer->status->value" />
                            </td>
                            <td class="table-cell text-right">
                                <div class="flex items-center justify-end gap-4">
                                    @can('update', $customer)
                                        <x-table-link href="{{ route('masters.customers.edit', $customer) }}" wire:navigate>{{ __('Edit') }}</x-table-link>
                                    @endcan

                                    @can('delete', $customer)
                                        <button
                                            type="button"
                                            class="rounded-md text-sm font-medium text-red-600 transition hover:text-red-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2"
                                            x-on:click="$dispatch('customer-delete-confirm', { url: '{{ route('masters.customers.destroy', $customer->id) }}' }); $dispatch('open-modal', 'confirm-customer-delete')"
                                        >{{ __('Delete') }}</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state :title="__('No customers found.')" :colspan="6" />
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $customers->links() }}</div>

    <x-modal name="confirm-customer-delete" maxWidth="md" focusable>
        <x-slot:header>
            <h2 class="text-lg font-semibold text-slate-900">
                {{ __('Are you sure you want to delete this customer?') }}
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('This action cannot be undone.') }}
            </p>
        </x-slot:header>

        <form :action="deleteAction" method="post" id="delete-customer-form">
            @csrf
            @method('DELETE')
        </form>

        <x-slot:footer>
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button form="delete-customer-form">
                {{ __('Delete Customer') }}
            </x-danger-button>
        </x-slot:footer>
    </x-modal>
</div>
