<div class="grid gap-6 lg:grid-cols-3">
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm lg:col-span-1">
        <h3 class="text-base font-semibold text-slate-900">{{ __('Add Category') }}</h3>
        <form wire:submit="saveCategory" class="mt-4 space-y-4">
            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input wire:model="name" id="name" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="description" :value="__('Description')" />
                <x-text-input wire:model="description" id="description" class="mt-1 block w-full" />
            </div>
            <x-primary-button>{{ __('Save Category') }}</x-primary-button>
        </form>
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm lg:col-span-2">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <h3 class="text-base font-semibold text-slate-900">{{ __('Categories') }}</h3>
            <x-export-excel-link module="categories" />
        </div>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Description') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($categories as $category)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $category->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $category->description ?? '—' }}</td>
                        <td class="px-4 py-3 capitalize text-slate-600">{{ $category->status->value }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-slate-500">{{ __('No categories yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
