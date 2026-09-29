@php
    extract(\App\Support\NonGstBillPresenter::vars($invoice, $business, get_defined_vars()));
@endphp

<div class="bill-classic">
    <h2 class="bill-classic-title">{{ $documentTitle ?? __('Bill / Invoice') }}</h2>

    <div class="bill-classic-top">
        <div class="seller-block">
            @if (($businessBranding['logo_url'] ?? null) && ! $isPurchase)
                <img src="{{ $businessBranding['logo_url'] }}" alt="{{ $business->name }}" class="branding-logo">
            @endif
            <div>
                <h1 class="seller-name">{{ $sellerName }}</h1>
                @if ($sellerAddress)
                    <p class="seller-meta">{{ $sellerAddress }}</p>
                @endif
                @if ($sellerPhone)
                    <p class="seller-line"><strong>{{ __('Phone') }}</strong> {{ $sellerPhone }}</p>
                @endif
            </div>
        </div>
        <table class="bill-meta-box">
            <tr>
                <td>{{ __('Bill No.') }}</td>
                <td class="mono">{{ $invoice->invoice_number }}</td>
            </tr>
            <tr>
                <td>{{ __('Date') }}</td>
                <td>{{ $invoice->invoice_date->format('d-m-Y') }}</td>
            </tr>
            @if ($invoice->due_date)
                <tr>
                    <td>{{ __('Due Date') }}</td>
                    <td>{{ $invoice->due_date->format('d-m-Y') }}</td>
                </tr>
            @endif
            <tr>
                <td>{{ __('Payment') }}</td>
                <td>{{ $invoice->settlement_mode->isCredit() ? __('Credit') : __('Cash / Bank') }}</td>
            </tr>
        </table>
    </div>

    <div class="bill-classic-parties">
        <div class="bill-party-panel">
            <p class="bill-party-label">{{ $isPurchase ? __('Supplier') : __('Bill To') }}</p>
            <p class="party-name">{{ $isPurchase ? $sellerName : $buyerName }}</p>
            @if ($isPurchase ? $sellerAddress : $buyerAddress)
                <p class="party-address">{{ $isPurchase ? $sellerAddress : $buyerAddress }}</p>
            @endif
        </div>
        <div class="bill-party-panel">
            <p class="bill-party-label">{{ $isPurchase ? __('Buyer') : __('Ship To') }}</p>
            <p class="party-name">{{ $isPurchase ? $buyerName : $invoice->party->name }}</p>
            @if ($shipAddress)
                <p class="party-address">{{ $shipAddress }}</p>
            @endif
        </div>
    </div>

    <table class="bill-items">
        <thead>
            <tr>
                <th class="center">#</th>
                <th>{{ __('Description') }}</th>
                <th class="right">{{ __('Qty') }}</th>
                <th class="right">{{ __('Rate') }}</th>
                <th class="right">{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lines as $index => $line)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $line->description }}</td>
                    <td class="right mono">{{ $line->quantity + 0 }}</td>
                    <td class="right mono">{{ \App\Support\Money::format($line->unit_price) }}</td>
                    <td class="right mono">{{ \App\Support\Money::format($line->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="bill-classic-bottom">
        <div class="bill-words">
            <span class="bill-words-label">{{ __('Amount in words') }}</span>
            {{ $amountInWords }}
            @if ($invoice->notes)
                <p class="bill-notes"><strong>{{ __('Notes') }}:</strong> {{ $invoice->notes }}</p>
            @endif
        </div>
        <div class="bill-totals-box">
            <div class="bill-grand">
                <span>{{ __('Total') }}</span>
                <span class="mono">{{ \App\Support\Money::format($invoice->total_amount) }}</span>
            </div>
        </div>
    </div>

    @include('sales.invoices.templates.partials.bill-footer', ['footerText' => __('This is a computer-generated bill (non-GST).')])
</div>
