@props([
    'title',
    'description' => null,
    'colspan' => null,
])

@if ($colspan)
    <tr>
        <td colspan="{{ $colspan }}" class="px-5 py-4">
            <p class="text-center text-sm text-slate-500">{{ $title }}</p>
        </td>
    </tr>
@else
    <div {{ $attributes }}>
        <div class="py-12 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                @isset($icon)
                    {{ $icon }}
                @else
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                @endisset
            </div>
            <p class="mt-4 text-sm font-medium text-slate-900">{{ $title }}</p>
            @if ($description)
                <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
            @endif
            @isset($action)
                <div class="mt-4">{{ $action }}</div>
            @endisset
        </div>
    </div>
@endif
