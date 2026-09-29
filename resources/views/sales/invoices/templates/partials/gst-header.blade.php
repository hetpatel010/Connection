@php
    $taxBreakdown = $taxBreakdown ?? \App\Support\GstTaxBreakdown::forInvoice($invoice, $business);
    $isInterState = $taxBreakdown['is_inter_state'];
    $isPurchase = $isPurchaseDocument ?? false;
    $variant = $variant ?? 'classic';

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

    $sellerName = $isPurchase ? $invoice->party->name : ($business->legal_name ?: $business->name);
    $buyerName = $isPurchase ? ($business->legal_name ?: $business->name) : $invoice->party->name;
    $sellerAddress = $isPurchase ? $partyAddress : $businessAddress;
    $buyerAddress = $isPurchase ? $businessAddress : $partyAddress;
    $shipAddress = collect([
        $invoice->party->shipping_address_line_1 ?? null,
        $invoice->party->shipping_address_line_2 ?? null,
        collect([
            $invoice->party->shipping_city ?? null,
            $invoice->party->shipping_state ?? null,
            $invoice->party->shipping_postal_code ?? null,
        ])->filter()->implode(', '),
    ])->filter()->implode("\n") ?: ($isPurchase ? $businessAddress : $partyAddress);
    $sellerGstin = $isPurchase ? $invoice->party->gstin : $business->gstin;
    $buyerGstin = $isPurchase ? $business->gstin : $invoice->party->gstin;
    $sellerPan = $isPurchase ? $invoice->party->pan : $business->pan;
    $sellerPhone = $isPurchase ? $invoice->party->phone : $business->phone;
    $sellerEmail = $isPurchase ? $invoice->party->email : $business->email;
@endphp

<table class="inv-grid gst-top {{ $variant === 'modern' ? 'modern' : '' }}">
    <tr>
        <td class="seller-cell cell-pad" style="width:58%;">
            <div class="seller-block">
                @if (($businessBranding['logo_url'] ?? null) && ! $isPurchase)
                    <img src="{{ $businessBranding['logo_url'] }}" alt="{{ $business->name }}" class="branding-logo">
                @endif
                <div>
                    <h1 class="seller-name">{{ $sellerName }}</h1>
                    @if ($sellerAddress)
                        <p class="seller-meta">{{ $sellerAddress }}</p>
                    @endif
                    @if ($sellerGstin)
                        <p class="seller-line"><strong>GSTIN</strong> <span class="mono">{{ $sellerGstin }}</span></p>
                    @endif
                    @if ($sellerPan)
                        <p class="seller-line"><strong>PAN</strong> <span class="mono">{{ $sellerPan }}</span></p>
                    @endif
                    @if ($business->state && ! $isPurchase)
                        <p class="seller-line"><strong>{{ __('State') }}</strong> {{ \App\Support\GstTaxBreakdown::formattedState($business->state, $business->gstin) }}</p>
                    @endif
                    @if ($sellerPhone || $sellerEmail)
                        <p class="seller-line">
                            @if ($sellerPhone)<strong>{{ __('Phone') }}</strong> {{ $sellerPhone }}@endif
                            @if ($sellerPhone && $sellerEmail) &nbsp;·&nbsp; @endif
                            @if ($sellerEmail)<strong>{{ __('Email') }}</strong> {{ $sellerEmail }}@endif
                        </p>
                    @endif
                    @if (($businessBranding['website'] ?? null) && ! $isPurchase)
                        <p class="seller-line"><strong>{{ __('Web') }}</strong> {{ $businessBranding['website'] }}</p>
                    @endif
                </div>
            </div>
        </td>
        <td class="meta-cell no-border-left" style="width:42%;">
            <div class="invoice-side">
                <div class="invoice-badge-wrap">
                    <div class="invoice-badge">{{ $documentTitle ?? __('Tax Invoice') }}</div>
                </div>
                <table class="invoice-meta-box">
                    <tr>
                        <td>{{ __('Invoice No.') }}</td>
                        <td class="mono">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Invoice Date') }}</td>
                        <td>{{ $invoice->invoice_date->format('d-m-Y') }}</td>
                    </tr>
                    @if ($invoice->due_date)
                        <tr>
                            <td>{{ __('Due Date') }}</td>
                            <td>{{ $invoice->due_date->format('d-m-Y') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td>{{ __('Place of Supply') }}</td>
                        <td>{{ $taxBreakdown['place_of_supply'] }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Supply Type') }}</td>
                        <td>{{ $isInterState ? __('Inter-State') : __('Intra-State') }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Reverse Charge') }}</td>
                        <td>{{ __('No') }}</td>
                    </tr>
                    <tr>
                        <td>{{ __('Payment Mode') }}</td>
                        <td>{{ $invoice->settlement_mode->isCredit() ? __('Credit') : __('Cash / Bank') }}</td>
                    </tr>
                    @if ($invoice->reference)
                        <tr>
                            <td>{{ __('Reference') }}</td>
                            <td>{{ $invoice->reference }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        </td>
    </tr>
</table>

<table class="inv-grid continuation">
    <tr>
        <td class="cell-pad" style="width:50%;">
            <p class="section-label">{{ $isPurchase ? __('Supplier (Bill From)') : __('Bill To / Buyer') }}</p>
            <p class="party-name">{{ $isPurchase ? $sellerName : $buyerName }}</p>
            @if ($isPurchase ? $sellerAddress : $buyerAddress)
                <p class="party-address">{{ $isPurchase ? $sellerAddress : $buyerAddress }}</p>
            @endif
            @if ($isPurchase ? $sellerGstin : $buyerGstin)
                <p class="party-detail"><strong>GSTIN</strong> <span class="mono">{{ $isPurchase ? $sellerGstin : $buyerGstin }}</span></p>
            @elseif ($invoice->party->billing_state)
                <p class="party-detail"><strong>{{ __('State') }}</strong> {{ \App\Support\GstTaxBreakdown::formattedState($invoice->party->billing_state, $invoice->party->gstin) }}</p>
            @endif
            @if ($invoice->party->phone && ! $isPurchase)
                <p class="party-detail"><strong>{{ __('Phone') }}</strong> {{ $invoice->party->phone }}</p>
            @endif
        </td>
        <td class="cell-pad no-border-left" style="width:50%;">
            <p class="section-label">{{ $isPurchase ? __('Buyer (Bill To)') : __('Ship To') }}</p>
            <p class="party-name">{{ $isPurchase ? $buyerName : $invoice->party->name }}</p>
            @if ($shipAddress)
                <p class="party-address">{{ $shipAddress }}</p>
            @endif
            @if ($buyerGstin)
                <p class="party-detail"><strong>GSTIN</strong> <span class="mono">{{ $buyerGstin }}</span></p>
            @endif
            @if ($isPurchase && $business->phone)
                <p class="party-detail"><strong>{{ __('Phone') }}</strong> {{ $business->phone }}</p>
            @endif
            @if (! $isPurchase && $invoice->party->phone && $shipAddress !== $partyAddress)
                <p class="party-detail"><strong>{{ __('Phone') }}</strong> {{ $invoice->party->phone }}</p>
            @endif
        </td>
    </tr>
</table>
