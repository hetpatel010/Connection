@php
    $businessAddress = collect([
        $business->address_line_1,
        $business->address_line_2,
        collect([$business->city, $business->state, $business->postal_code])->filter()->implode(', '),
    ])->filter()->implode("\n");
    $partyAddress = collect([
        $invoice->party->billing_address_line_1 ?? null,
        $invoice->party->billing_address_line_2 ?? null,
        collect([
            $invoice->party->billing_city ?? null,
            $invoice->party->billing_state ?? null,
            $invoice->party->billing_postal_code ?? null,
        ])->filter()->implode(', '),
    ])->filter()->implode("\n");
    $isPurchase = $isPurchaseDocument ?? false;
@endphp

<div class="header-grid">
    @if ($isPurchase)
        <div class="party-box">
            <h3>{{ __('Supplier') }}</h3>
            <p style="margin:0;font-weight:700;">{{ $invoice->party->name }}</p>
            @if ($partyAddress)
                <p class="muted" style="margin:0.35rem 0 0;white-space:pre-line;">{{ $partyAddress }}</p>
            @endif
            @if ($showGstDetails ?? true)
                @if ($invoice->party->gstin)
                    <p class="muted" style="margin:0.35rem 0 0;">GSTIN: <span class="mono">{{ $invoice->party->gstin }}</span></p>
                @endif
            @endif
            @if ($invoice->party->phone)
                <p class="muted" style="margin:0.15rem 0 0;">{{ __('Phone') }}: {{ $invoice->party->phone }}</p>
            @endif
        </div>
        <div class="party-box">
            <h3>{{ __('Buyer') }}</h3>
            <p style="margin:0;font-weight:700;">{{ $business->legal_name ?: $business->name }}</p>
            @if ($businessAddress)
                <p class="muted" style="margin:0.35rem 0 0;white-space:pre-line;">{{ $businessAddress }}</p>
            @endif
            @if ($showGstDetails ?? true)
                @if ($business->gstin)
                    <p class="muted" style="margin:0.35rem 0 0;">GSTIN: <span class="mono">{{ $business->gstin }}</span></p>
                @endif
            @endif
            @if ($business->phone)
                <p class="muted" style="margin:0.15rem 0 0;">{{ __('Phone') }}: {{ $business->phone }}</p>
            @endif
        </div>
    @else
        <div class="party-box">
            <h3>{{ __('Seller') }}</h3>
            <p style="margin:0;font-weight:700;">{{ $business->legal_name ?: $business->name }}</p>
            @if ($businessAddress)
                <p class="muted" style="margin:0.35rem 0 0;white-space:pre-line;">{{ $businessAddress }}</p>
            @endif
            @if ($showGstDetails ?? true)
                @if ($business->gstin)
                    <p class="muted" style="margin:0.35rem 0 0;">GSTIN: <span class="mono">{{ $business->gstin }}</span></p>
                @endif
            @endif
            @if ($business->phone)
                <p class="muted" style="margin:0.15rem 0 0;">{{ __('Phone') }}: {{ $business->phone }}</p>
            @endif
        </div>
        <div class="party-box">
            <h3>{{ __('Buyer') }}</h3>
            <p style="margin:0;font-weight:700;">{{ $invoice->party->name }}</p>
            @if ($partyAddress)
                <p class="muted" style="margin:0.35rem 0 0;white-space:pre-line;">{{ $partyAddress }}</p>
            @endif
            @if ($showGstDetails ?? true)
                @if ($invoice->party->gstin)
                    <p class="muted" style="margin:0.35rem 0 0;">GSTIN: <span class="mono">{{ $invoice->party->gstin }}</span></p>
                @elseif ($invoice->party->billing_state)
                    <p class="muted" style="margin:0.35rem 0 0;">{{ __('Place of supply') }}: {{ $invoice->party->billing_state }}</p>
                @endif
            @endif
        </div>
    @endif
</div>
