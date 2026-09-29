<div>
    <form wire:submit="save" class="space-y-8">
        <x-card>
            <h3 class="text-sm font-semibold text-slate-900">{{ __('GST tax invoices') }}</h3>
            <p class="mt-1 text-xs text-slate-500">{{ __('Default layout when creating GST invoices (with tax breakdown).') }}</p>

            <div class="mt-4">
                <x-input-label for="defaultTemplateGst" :value="__('Default GST template')" />
                <x-select-input wire:model="defaultTemplateGst" id="defaultTemplateGst" class="mt-1.5 w-full max-w-md">
                    @foreach ($gstTemplates as $template)
                        <option value="{{ $template['slug'] }}">{{ $template['name'] }}</option>
                    @endforeach
                </x-select-input>
                <x-input-error :messages="$errors->get('defaultTemplateGst')" class="mt-2" />
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($gstTemplates as $template)
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                        <x-invoice-template-preview :slug="$template['slug']" class="rounded-none border-0 border-b border-slate-200" />
                        <div class="p-3">
                            <p class="text-sm font-medium text-slate-900">{{ $template['name'] }}</p>
                            <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ $template['description'] }}</p>
                            <a href="{{ route('settings.invoice-templates.preview', $template['slug']) }}" target="_blank" class="mt-2 inline-flex text-xs font-medium text-indigo-600 hover:text-indigo-500">
                                {{ __('Preview') }} &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-card>
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Non-GST bills') }}</h3>
            <p class="mt-1 text-xs text-slate-500">{{ __('Default layout for bills without GST (no tax columns or GSTIN blocks).') }}</p>

            <div class="mt-4">
                <x-input-label for="defaultTemplateNonGst" :value="__('Default non-GST template')" />
                <x-select-input wire:model="defaultTemplateNonGst" id="defaultTemplateNonGst" class="mt-1.5 w-full max-w-md">
                    @foreach ($nonGstTemplates as $template)
                        <option value="{{ $template['slug'] }}">{{ $template['name'] }}</option>
                    @endforeach
                </x-select-input>
                <x-input-error :messages="$errors->get('defaultTemplateNonGst')" class="mt-2" />
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                @foreach ($nonGstTemplates as $template)
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                        <x-invoice-template-preview :slug="$template['slug']" class="rounded-none border-0 border-b border-slate-200" />
                        <div class="p-3">
                            <p class="text-sm font-medium text-slate-900">{{ $template['name'] }}</p>
                            <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ $template['description'] }}</p>
                            <a href="{{ route('settings.invoice-templates.preview', $template['slug']) }}" target="_blank" class="mt-2 inline-flex text-xs font-medium text-indigo-600 hover:text-indigo-500">
                                {{ __('Preview') }} &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-invoice-template-custom-request />

        <div class="flex justify-end">
            <x-primary-button type="submit">{{ __('Save defaults') }}</x-primary-button>
        </div>
    </form>
</div>
