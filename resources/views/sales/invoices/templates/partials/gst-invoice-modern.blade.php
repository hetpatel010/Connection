@php
    extract(\App\Support\GstInvoicePresenter::vars($invoice, $business, get_defined_vars()));
@endphp

<div class="gst-modern">
    <header class="mod-banner">
        <div class="mod-banner-inner">
            <div class="mod-brand">
                @if (($businessBranding['logo_url'] ?? null) && ! $isPurchase)
                    <img src="{{ $businessBranding['logo_url'] }}" alt="{{ $business->name }}" class="mod-logo">
                @endif
                <div>
                    <h1 class="mod-company">{{ $sellerName }}</h1>
                    @if ($sellerGstin)
                        <p class="mod-gstin">GSTIN <span class="mono">{{ $sellerGstin }}</span></p>
                    @endif
                </div>
            </div>
            <div class="mod-title-block">
                <p class="mod-title">{{ $documentTitle ?? __('Tax Invoice') }}</p>
                <p class="mod-number mono">{{ $invoice->invoice_number }}</p>
            </div>
        </div>
    </header>

    <div class="mod-body">
        <div class="mod-meta-cards">
            <div class="mod-card">
                <span class="mod-card-label">{{ __('Invoice Date') }}</span>
                <span class="mod-card-value">{{ $invoice->invoice_date->format('d-m-Y') }}</span>
            </div>
            <div class="mod-card">
                <span class="mod-card-label">{{ __('Place of Supply') }}</span>
                <span class="mod-card-value">{{ $taxBreakdown['place_of_supply'] }}</span>
            </div>
            <div class="mod-card">
                <span class="mod-card-label">{{ __('Supply Type') }}</span>
                <span class="mod-card-value">{{ $isInterState ? __('Inter-State') : __('Intra-State') }}</span>
            </div>
            <div class="mod-card">
                <span class="mod-card-label">{{ __('Payment') }}</span>
                <span class="mod-card-value">{{ $invoice->settlement_mode->isCredit() ? __('Credit') : __('Cash / Bank') }}</span>
            </div>
        </div>

        @if ($sellerAddress || $sellerPhone || $sellerEmail)
            <p class="mod-seller-meta">
                @if ($sellerAddress){{ $sellerAddress }}@endif
                @if ($sellerPhone) · {{ __('Phone') }} {{ $sellerPhone }}@endif
                @if ($sellerEmail) · {{ $sellerEmail }}@endif
            </p>
        @endif

        <div class="mod-parties">
            <div class="mod-party-card">
                <p class="mod-party-label">{{ $isPurchase ? __('Supplier (Bill From)') : __('Bill To / Buyer') }}</p>
                <p class="mod-party-name">{{ $isPurchase ? $sellerName : $buyerName }}</p>
                @if ($isPurchase ? $sellerAddress : $buyerAddress)
                    <p class="mod-party-text">{{ $isPurchase ? $sellerAddress : $buyerAddress }}</p>
                @endif
                @if ($isPurchase ? $sellerGstin : $buyerGstin)
                    <p class="mod-party-text">GSTIN <span class="mono">{{ $isPurchase ? $sellerGstin : $buyerGstin }}</span></p>
                @endif
            </div>
            <div class="mod-party-card">
                <p class="mod-party-label">{{ $isPurchase ? __('Buyer (Bill To)') : __('Ship To') }}</p>
                <p class="mod-party-name">{{ $isPurchase ? $buyerName : $invoice->party->name }}</p>
                @if ($shipAddress)
                    <p class="mod-party-text">{{ $shipAddress }}</p>
                @endif
            </div>
        </div>

        <table class="mod-items">
            <thead>
                <tr>
                    <th>#</th>
                    <th class="desc">{{ __('Description of Goods / Services') }}</th>
                    <th>HSN/SAC</th>
                    <th class="right">{{ __('Qty') }}</th>
                    <th class="right">{{ __('Rate') }}</th>
                    <th class="right">{{ __('Taxable') }}</th>
                    @if ($isInterState)
                        <th colspan="2" class="center">{{ __('IGST') }}</th>
                    @else
                        <th colspan="2" class="center">{{ __('CGST') }}</th>
                        <th colspan="2" class="center">{{ __('SGST') }}</th>
                    @endif
                    <th class="right">{{ __('Total') }}</th>
                </tr>
                <tr class="mod-subhead">
                    @if ($isInterState)
                        <th colspan="6"></th>
                        <th class="center">%</th>
                        <th class="right">{{ __('Amt') }}</th>
                        <th></th>
                    @else
                        <th colspan="6"></th>
                        <th class="center">%</th>
                        <th class="right">{{ __('Amt') }}</th>
                        <th class="center">%</th>
                        <th class="right">{{ __('Amt') }}</th>
                        <th></th>
                    @endif
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
                            <td class="center">{{ $lineTax['igst_rate'] + 0 }}%</td>
                            <td class="right mono">{{ \App\Support\Money::format($lineTax['igst']) }}</td>
                        @else
                            <td class="center">{{ $lineTax['cgst_rate'] + 0 }}%</td>
                            <td class="right mono">{{ \App\Support\Money::format($lineTax['cgst']) }}</td>
                            <td class="center">{{ $lineTax['sgst_rate'] + 0 }}%</td>
                            <td class="right mono">{{ \App\Support\Money::format($lineTax['sgst']) }}</td>
                        @endif
                        <td class="right mono mod-line-total">{{ \App\Support\Money::format($line->line_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mod-summary">
            <div class="mod-summary-left">
                <p class="mod-section-title">{{ __('HSN/SAC Summary') }}</p>
                <table class="mod-hsn">
                    <thead>
                        <tr>
                            <th>HSN/SAC</th>
                            <th class="right">{{ __('Taxable') }}</th>
                            @if ($isInterState)
                                <th class="right">IGST</th>
                            @else
                                <th class="right">CGST</th>
                                <th class="right">SGST</th>
                            @endif
                            <th class="right">{{ __('Tax') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($taxBreakdown['hsn_summary'] as $row)
                            <tr>
                                <td class="mono">{{ $row['hsn'] }}</td>
                                <td class="right mono">{{ \App\Support\Money::format($row['taxable']) }}</td>
                                @if ($isInterState)
                                    <td class="right mono">{{ \App\Support\Money::format($row['igst']) }}</td>
                                @else
                                    <td class="right mono">{{ \App\Support\Money::format($row['cgst']) }}</td>
                                    <td class="right mono">{{ \App\Support\Money::format($row['sgst']) }}</td>
                                @endif
                                <td class="right mono">{{ \App\Support\Money::format($row['total_tax']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mod-words">
                    <span class="mod-words-label">{{ __('Amount Chargeable (in words)') }}</span>
                    {{ $amountInWords }}
                </div>
                @if ($invoice->notes)
                    <p class="mod-notes"><strong>{{ __('Notes') }}:</strong> {{ $invoice->notes }}</p>
                @endif
            </div>
            <div class="mod-totals-panel">
                <div class="mod-total-line"><span>{{ __('Taxable Amount') }}</span><span class="mono">{{ \App\Support\Money::format($invoice->subtotal_amount) }}</span></div>
                @if ($isInterState)
                    <div class="mod-total-line"><span>{{ __('IGST') }}</span><span class="mono">{{ \App\Support\Money::format($taxBreakdown['igst']) }}</span></div>
                @else
                    <div class="mod-total-line"><span>{{ __('CGST') }}</span><span class="mono">{{ \App\Support\Money::format($taxBreakdown['cgst']) }}</span></div>
                    <div class="mod-total-line"><span>{{ __('SGST') }}</span><span class="mono">{{ \App\Support\Money::format($taxBreakdown['sgst']) }}</span></div>
                @endif
                <div class="mod-total-line"><span>{{ __('Total Tax') }}</span><span class="mono">{{ \App\Support\Money::format($invoice->tax_amount) }}</span></div>
                <div class="mod-grand">
                    <span>{{ __('Grand Total') }}</span>
                    <span class="mono">{{ \App\Support\Money::format($invoice->total_amount) }}</span>
                </div>
            </div>
        </div>

        <footer class="mod-footer">
            <div>
                @if ($businessBranding['bank_details'] ?? null)
                    <p class="mod-footer-block"><strong>{{ __('Bank Details') }}</strong><br>{{ $businessBranding['bank_details'] }}</p>
                @endif
                @if ($businessBranding['terms_and_conditions'] ?? null)
                    <p class="mod-footer-block"><strong>{{ __('Terms & Conditions') }}</strong><br>{!! nl2br(e($businessBranding['terms_and_conditions'])) !!}</p>
                @endif
            </div>
            <div class="mod-sign">
                <div class="mod-sign-line"></div>
                <p>{{ $businessBranding['signature_label'] ?? __('For') }} {{ $business->legal_name ?: $business->name }}</p>
                <p class="muted">{{ __('Authorised Signatory') }}</p>
            </div>
        </footer>
    </div>

    <p class="mod-footnote">
        {{ $footerText ?? __('This is a computer-generated tax invoice.') }}
        · {{ branding('product.name', config('app.name')) }}
    </p>
</div>
