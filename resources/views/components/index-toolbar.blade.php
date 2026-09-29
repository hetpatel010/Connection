@props([
    'exportModule' => null,
    'exportSearch' => '',
    'exportType' => '',
    'exportGroup' => '',
    'exportStatus' => '',
    'exportView' => '',
])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between']) }}>
    @isset($search)
        {{ $search }}
    @endisset

    @if ($exportModule || isset($actions))
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            @if ($exportModule)
                <x-export-excel-link
                    :module="$exportModule"
                    :search="$exportSearch"
                    :type="$exportType"
                    :group="$exportGroup"
                    :status="$exportStatus"
                    :view="$exportView"
                />
            @endif

            @isset($actions)
                {{ $actions }}
            @endisset
        </div>
    @endif
</div>
