<div>
    @if ($financialYears->isNotEmpty())
        <label for="financial-year-selector" class="sr-only">{{ __('Financial Year') }}</label>
        <x-select-input id="financial-year-selector" wire:model.live="selectedFinancialYearId">
            @foreach ($financialYears as $financialYear)
                <option value="{{ $financialYear->id }}">
                    FY {{ $financialYear->label }} ({{ $financialYear->status->label() }})
                </option>
            @endforeach
        </x-select-input>
    @endif
</div>
