@php
    extract(\App\Support\NonGstBillPresenter::vars($invoice, $business, get_defined_vars()));
@endphp

<div class="bill-minimal">
    <header class="bill-min-header">
        <div class="bill-min-left">
            @if (($businessBranding['logo_url'] ?? null) && ! $isPurchase)
                <img src="{{ $businessBranding['logo_url'] }}" alt="{{ $business->name }}" class="bill-min-logo">
            @endif
            <div>
                <h1 class="bill-min-company">{{ $sellerName }}</h1>
                @if ($sellerAddress)
                    <p class="bill-min-meta">{{ $sellerAddress }}</p>
                @endif
            </div>
        </div>
        <div class="bill-min-right">
            <p class="bill-min-label">{{ $documentTitle ?? __('Bill / Invoice') }}</p>
            <p class="bill-min-no mono">{{ $invoice->invoice_number }}</p>
            <p class="bill-min-date">{{ $invoice->invoice_date->format('d M Y') }}</p>
        </div>
    </header>

    <div class="bill-min-strip">
        <span><strong>{{ __('Payment') }}</strong> {{ $invoice->settlement_mode->isCredit() ? __('Credit') : __('Cash / Bank') }}</span>
        @if ($invoice->due_date)
            <span><strong>{{ __('Due') }}</strong> {{ $invoice->due_date->format('d-m-Y') }}</span>
        @endif
    </div>

    <div class="bill-min-parties">
        <div>
            <p class="bill-min-party-label">{{ $isPurchase ? __('Supplier') : __('Bill To') }}</p>
            <p class="party-name">{{ $isPurchase ? $sellerName : $buyerName }}</p>
            @if ($isPurchase ? $sellerAddress : $buyerAddress)
                <p class="bill-min-party-text">{{ $isPurchase ? $sellerAddress : $buyerAddress }}</p>
            @endif
        </div>
        <div>
            <p class="bill-min-party-label">{{ $isPurchase ? __('Buyer') : __('Ship To') }}</p>
            <p class="party-name">{{ $isPurchase ? $buyerName : $invoice->party->name }}</p>
            @if ($shipAddress)
                <p class="bill-min-party-text">{{ $shipAddress }}</p>
            @endif
        </div>
    </div>

    <table class="bill-min-items">
        <thead>
            <tr>
                <th>{{ __('Item') }}</th>
                <th class="right">{{ __('Qty') }}</th>
                <th class="right">{{ __('Rate') }}</th>
                <th class="right">{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lines as $line)
                <tr>
                    <td>{{ $line->description }}</td>
                    <td class="right mono">{{ $line->quantity + 0 }}</td>
                    <td class="right mono">{{ \App\Support\Money::format($line->unit_price) }}</td>
                    <td class="right mono">{{ \App\Support\Money::format($line->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="bill-min-bottom">
        <p class="bill-min-words">
            <span class="bill-min-words-label">{{ __('In words') }}</span>
            {{ $amountInWords }}
        </p>
        <div class="bill-min-total">
            <span>{{ __('Total') }}</span>
            <span class="mono">{{ \App\Support\Money::format($invoice->total_amount) }}</span>
        </div>
    </div>

    @if ($invoice->notes)
        <p class="bill-min-notes"><strong>{{ __('Notes') }}:</strong> {{ $invoice->notes }}</p>
    @endif

    @include('sales.invoices.templates.partials.bill-footer', ['footerText' => __('Non-GST bill.')])
</div>
