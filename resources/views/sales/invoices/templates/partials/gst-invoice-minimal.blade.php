@php
    extract(\App\Support\GstInvoicePresenter::vars($invoice, $business, get_defined_vars()));
@endphp

<div class="gst-minimal">
    <header class="min-header">
        <div class="min-header-left">
            @if (($businessBranding['logo_url'] ?? null) && ! $isPurchase)
                <img src="{{ $businessBranding['logo_url'] }}" alt="{{ $business->name }}" class="min-logo">
            @endif
            <div>
                <h1 class="min-company">{{ $sellerName }}</h1>
                @if ($sellerAddress)
                    <p class="min-address">{{ $sellerAddress }}</p>
                @endif
                <div class="min-id-lines">
                    @if ($sellerGstin)
                        <span><strong>GSTIN</strong> <span class="mono">{{ $sellerGstin }}</span></span>
                    @endif
                    @if ($sellerPan)
                        <span><strong>PAN</strong> <span class="mono">{{ $sellerPan }}</span></span>
                    @endif
                    @if ($sellerPhone)
                        <span><strong>{{ __('Phone') }}</strong> {{ $sellerPhone }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="min-header-right">
            <p class="min-doc-label">{{ $documentTitle ?? __('Tax Invoice') }}</p>
            <p class="min-doc-no mono">{{ $invoice->invoice_number }}</p>
            <p class="min-doc-date">{{ $invoice->invoice_date->format('d M Y') }}</p>
        </div>
    </header>

    <div class="min-meta-strip">
        <span><strong>{{ __('Place of Supply') }}</strong> {{ $taxBreakdown['place_of_supply'] }}</span>
        <span><strong>{{ __('Supply') }}</strong> {{ $isInterState ? __('Inter-State') : __('Intra-State') }}</span>
        <span><strong>{{ __('Payment') }}</strong> {{ $invoice->settlement_mode->isCredit() ? __('Credit') : __('Cash / Bank') }}</span>
        @if ($invoice->due_date)
            <span><strong>{{ __('Due') }}</strong> {{ $invoice->due_date->format('d-m-Y') }}</span>
        @endif
    </div>

    <div class="min-parties">
        <div class="min-party">
            <p class="min-party-label">{{ $isPurchase ? __('Supplier') : __('Bill To') }}</p>
            <p class="min-party-name">{{ $isPurchase ? $sellerName : $buyerName }}</p>
            @if ($isPurchase ? $sellerAddress : $buyerAddress)
                <p class="min-party-text">{{ $isPurchase ? $sellerAddress : $buyerAddress }}</p>
            @endif
            @if ($isPurchase ? $sellerGstin : $buyerGstin)
                <p class="min-party-text"><strong>GSTIN</strong> <span class="mono">{{ $isPurchase ? $sellerGstin : $buyerGstin }}</span></p>
            @endif
        </div>
        <div class="min-party">
            <p class="min-party-label">{{ $isPurchase ? __('Buyer') : __('Ship To') }}</p>
            <p class="min-party-name">{{ $isPurchase ? $buyerName : $invoice->party->name }}</p>
            @if ($shipAddress)
                <p class="min-party-text">{{ $shipAddress }}</p>
            @endif
        </div>
    </div>

    <table class="min-items">
        <thead>
            <tr>
                <th>#</th>
                <th class="desc">{{ __('Description') }}</th>
                <th>HSN</th>
                <th class="right">{{ __('Qty') }}</th>
                <th class="right">{{ __('Rate') }}</th>
                <th class="right">{{ __('Taxable') }}</th>
                @if ($isInterState)
                    <th class="right">{{ __('IGST') }}</th>
                @else
                    <th class="right">{{ __('CGST') }}</th>
                    <th class="right">{{ __('SGST') }}</th>
                @endif
                <th class="right">{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lines as $index => $line)
                @php
                    $lineTax = \App\Support\GstTaxBreakdown::lineTaxAmounts($line, $isInterState);
                    $taxable = \App\Support\GstTaxBreakdown::taxableAmount($line);
                @endphp
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="desc">{{ $line->description }}</td>
                    <td class="center mono">{{ $line->hsn_sac ?: '—' }}</td>
                    <td class="right mono">{{ $line->quantity + 0 }}</td>
                    <td class="right mono">{{ \App\Support\Money::format($line->unit_price) }}</td>
                    <td class="right mono">{{ \App\Support\Money::format($taxable) }}</td>
                    @if ($isInterState)
                        <td class="right mono">{{ $lineTax['igst_rate'] + 0 }}% · {{ \App\Support\Money::format($lineTax['igst']) }}</td>
                    @else
                        <td class="right mono">{{ $lineTax['cgst_rate'] + 0 }}% · {{ \App\Support\Money::format($lineTax['cgst']) }}</td>
                        <td class="right mono">{{ $lineTax['sgst_rate'] + 0 }}% · {{ \App\Support\Money::format($lineTax['sgst']) }}</td>
                    @endif
                    <td class="right mono">{{ \App\Support\Money::format($line->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="min-bottom">
        <div class="min-bottom-left">
            <p class="min-section-title">{{ __('HSN Summary') }}</p>
            <table class="min-hsn">
                @foreach ($taxBreakdown['hsn_summary'] as $row)
                    <tr>
                        <td class="mono">{{ $row['hsn'] }}</td>
                        <td class="right mono">{{ \App\Support\Money::format($row['taxable']) }}</td>
                        <td class="right mono">{{ \App\Support\Money::format($row['total_tax']) }}</td>
                    </tr>
                @endforeach
            </table>
            <p class="min-words">
                <span class="min-words-label">{{ __('In words') }}</span>
                {{ $amountInWords }}
            </p>
            @if ($invoice->notes)
                <p class="min-notes"><strong>{{ __('Notes') }}:</strong> {{ $invoice->notes }}</p>
            @endif
        </div>
        <div class="min-totals">
            <div class="min-total-row"><span>{{ __('Taxable') }}</span><span class="mono">{{ \App\Support\Money::format($invoice->subtotal_amount) }}</span></div>
            @if ($isInterState)
                <div class="min-total-row"><span>{{ __('IGST') }}</span><span class="mono">{{ \App\Support\Money::format($taxBreakdown['igst']) }}</span></div>
            @else
                <div class="min-total-row"><span>{{ __('CGST') }}</span><span class="mono">{{ \App\Support\Money::format($taxBreakdown['cgst']) }}</span></div>
                <div class="min-total-row"><span>{{ __('SGST') }}</span><span class="mono">{{ \App\Support\Money::format($taxBreakdown['sgst']) }}</span></div>
            @endif
            <div class="min-total-row"><span>{{ __('Total Tax') }}</span><span class="mono">{{ \App\Support\Money::format($invoice->tax_amount) }}</span></div>
            <div class="min-grand"><span>{{ __('Grand Total') }}</span><span class="mono">{{ \App\Support\Money::format($invoice->total_amount) }}</span></div>
        </div>
    </div>

    <footer class="min-footer">
        <div class="min-footer-left">
            @if ($businessBranding['bank_details'] ?? null)
                <p><strong>{{ __('Bank') }}</strong> {{ $businessBranding['bank_details'] }}</p>
            @endif
            @if ($businessBranding['terms_and_conditions'] ?? null)
                <p class="min-terms">{!! nl2br(e($businessBranding['terms_and_conditions'])) !!}</p>
            @endif
        </div>
        <div class="min-sign">
            <div class="min-sign-line"></div>
            <p>{{ $businessBranding['signature_label'] ?? __('For') }} {{ $business->legal_name ?: $business->name }}</p>
            <p class="muted">{{ __('Authorised Signatory') }}</p>
        </div>
    </footer>

    <p class="min-footnote">
        {{ $footerText ?? __('This is a computer-generated tax invoice.') }}
        · {{ branding('product.name', config('app.name')) }}
    </p>
</div>
