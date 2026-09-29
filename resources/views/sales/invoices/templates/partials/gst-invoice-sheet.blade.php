@php
    extract(\App\Support\GstInvoicePresenter::vars($invoice, $business, get_defined_vars()));
@endphp

<table class="invoice-sheet classic">
    @if ($isInterState)
        <colgroup>
            <col style="width:4%">
            <col style="width:24%">
            <col style="width:8%">
            <col style="width:6%">
            <col style="width:9%">
            <col style="width:9%">
            <col style="width:5%">
            <col style="width:9%">
            <col style="width:10%">
        </colgroup>
    @else
        <colgroup>
            <col style="width:4%">
            <col style="width:20%">
            <col style="width:7%">
            <col style="width:5%">
            <col style="width:8%">
            <col style="width:8%">
            <col style="width:4%">
            <col style="width:7%">
            <col style="width:4%">
            <col style="width:7%">
            <col style="width:9%">
        </colgroup>
    @endif

    {{-- Header --}}
    <tr class="row-header">
        <td colspan="{{ $splitLeft }}" class="cell-tight">
            <div class="seller-block">
                @if (($businessBranding['logo_url'] ?? null) && ! $isPurchase)
                    <img src="{{ $businessBranding['logo_url'] }}" alt="{{ $business->name }}" class="branding-logo">
                @endif
                <div>
                    <div class="seller-name">{{ $sellerName }}</div>
                    @if ($sellerAddress)
                        <div class="seller-meta">{{ $sellerAddress }}</div>
                    @endif
                    @if ($sellerGstin)
                        <div class="seller-line"><strong>GSTIN</strong> <span class="mono">{{ $sellerGstin }}</span></div>
                    @endif
                    @if ($sellerPan)
                        <div class="seller-line"><strong>PAN</strong> <span class="mono">{{ $sellerPan }}</span></div>
                    @endif
                    @if ($business->state && ! $isPurchase)
                        <div class="seller-line"><strong>{{ __('State') }}</strong> <span>{{ \App\Support\GstTaxBreakdown::formattedState($business->state, $business->gstin) }}</span></div>
                    @endif
                    @if ($sellerPhone || $sellerEmail)
                        <div class="seller-line">
                            @if ($sellerPhone)<strong>{{ __('Phone') }}</strong> {{ $sellerPhone }}@endif
                            @if ($sellerPhone && $sellerEmail) · @endif
                            @if ($sellerEmail)<strong>{{ __('Email') }}</strong> {{ $sellerEmail }}@endif
                        </div>
                    @endif
                    @if (($businessBranding['website'] ?? null) && ! $isPurchase)
                        <div class="seller-line"><strong>{{ __('Web') }}</strong> {{ $businessBranding['website'] }}</div>
                    @endif
                </div>
            </div>
        </td>
        <td colspan="{{ $splitRight }}" class="cell-none">
            <div class="invoice-title">{{ $documentTitle ?? __('Tax Invoice') }}</div>
            <div class="meta-list">
                <div class="meta-row">
                    <span class="meta-label">{{ __('Invoice No.') }}</span>
                    <span class="meta-value mono">{{ $invoice->invoice_number }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">{{ __('Invoice Date') }}</span>
                    <span class="meta-value">{{ $invoice->invoice_date->format('d-m-Y') }}</span>
                </div>
                @if ($invoice->due_date)
                    <div class="meta-row">
                        <span class="meta-label">{{ __('Due Date') }}</span>
                        <span class="meta-value">{{ $invoice->due_date->format('d-m-Y') }}</span>
                    </div>
                @endif
                <div class="meta-row">
                    <span class="meta-label">{{ __('Place of Supply') }}</span>
                    <span class="meta-value">{{ $taxBreakdown['place_of_supply'] }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">{{ __('Supply Type') }}</span>
                    <span class="meta-value">{{ $isInterState ? __('Inter-State') : __('Intra-State') }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">{{ __('Reverse Charge') }}</span>
                    <span class="meta-value">{{ __('No') }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">{{ __('Payment Mode') }}</span>
                    <span class="meta-value">{{ $invoice->settlement_mode->isCredit() ? __('Credit') : __('Cash / Bank') }}</span>
                </div>
                @if ($invoice->reference)
                    <div class="meta-row">
                        <span class="meta-label">{{ __('Reference') }}</span>
                        <span class="meta-value">{{ $invoice->reference }}</span>
                    </div>
                @endif
            </div>
        </td>
    </tr>

    {{-- Parties --}}
    <tr>
        <td colspan="{{ $splitLeft }}" class="cell-tight">
            <div class="section-label">{{ $isPurchase ? __('Supplier (Bill From)') : __('Bill To / Buyer') }}</div>
            <div class="party-name">{{ $isPurchase ? $sellerName : $buyerName }}</div>
            @if ($isPurchase ? $sellerAddress : $buyerAddress)
                <div class="party-address">{{ $isPurchase ? $sellerAddress : $buyerAddress }}</div>
            @endif
            @if ($isPurchase ? $sellerGstin : $buyerGstin)
                <div class="party-detail"><strong>GSTIN</strong> <span class="mono">{{ $isPurchase ? $sellerGstin : $buyerGstin }}</span></div>
            @elseif ($invoice->party->billing_state)
                <div class="party-detail"><strong>{{ __('State') }}</strong> <span>{{ \App\Support\GstTaxBreakdown::formattedState($invoice->party->billing_state, $invoice->party->gstin) }}</span></div>
            @endif
            @if ($invoice->party->phone && ! $isPurchase)
                <div class="party-detail"><strong>{{ __('Phone') }}</strong> {{ $invoice->party->phone }}</div>
            @endif
        </td>
        <td colspan="{{ $splitRight }}" class="cell-tight">
            <div class="section-label">{{ $isPurchase ? __('Buyer (Bill To)') : __('Ship To') }}</div>
            <div class="party-name">{{ $isPurchase ? $buyerName : $invoice->party->name }}</div>
            @if ($shipAddress)
                <div class="party-address">{{ $shipAddress }}</div>
            @endif
            @if ($buyerGstin)
                <div class="party-detail"><strong>GSTIN</strong> <span class="mono">{{ $buyerGstin }}</span></div>
            @endif
            @if ($isPurchase && $business->phone)
                <div class="party-detail"><strong>{{ __('Phone') }}</strong> {{ $business->phone }}</div>
            @endif
        </td>
    </tr>

    {{-- Items header --}}
    <tr class="items-head">
        <th rowspan="2">#</th>
        <th rowspan="2" class="desc">{{ __('Description of Goods / Services') }}</th>
        <th rowspan="2">HSN/SAC</th>
        <th rowspan="2">{{ __('Qty') }}</th>
        <th rowspan="2">{{ __('Rate') }}</th>
        <th rowspan="2">{{ __('Taxable') }}</th>
        @if ($isInterState)
            <th colspan="2">{{ __('IGST') }}</th>
        @else
            <th colspan="2">{{ __('CGST') }}</th>
            <th colspan="2">{{ __('SGST') }}</th>
        @endif
        <th rowspan="2">{{ __('Total') }}</th>
    </tr>
    <tr class="subhead">
        @if ($isInterState)
            <th>%</th>
            <th>{{ __('Amt') }}</th>
        @else
            <th>%</th>
            <th>{{ __('Amt') }}</th>
            <th>%</th>
            <th>{{ __('Amt') }}</th>
        @endif
    </tr>

    {{-- Item rows --}}
    @foreach ($invoice->lines as $index => $line)
            @php
                $lineTax = \App\Support\GstTaxBreakdown::lineTaxAmounts($line, $isInterState);
                $taxable = \App\Support\GstTaxBreakdown::taxableAmount($line);
            @endphp
            <tr class="data-row">
                <td class="center">{{ $index + 1 }}</td>
                <td class="desc">{{ $line->description }}</td>
                <td class="center mono">{{ $line->hsn_sac ?: '—' }}</td>
                <td class="right mono nowrap">{{ $line->quantity + 0 }}</td>
                <td class="right mono nowrap">{{ \App\Support\Money::format($line->unit_price) }}</td>
                <td class="right mono nowrap">{{ \App\Support\Money::format($taxable) }}</td>
                @if ($isInterState)
                    <td class="center nowrap">{{ $lineTax['igst_rate'] + 0 }}%</td>
                    <td class="right mono nowrap">{{ \App\Support\Money::format($lineTax['igst']) }}</td>
                @else
                    <td class="center nowrap">{{ $lineTax['cgst_rate'] + 0 }}%</td>
                    <td class="right mono nowrap">{{ \App\Support\Money::format($lineTax['cgst']) }}</td>
                    <td class="center nowrap">{{ $lineTax['sgst_rate'] + 0 }}%</td>
                    <td class="right mono nowrap">{{ \App\Support\Money::format($lineTax['sgst']) }}</td>
                @endif
                <td class="right mono nowrap">{{ \App\Support\Money::format($line->line_total) }}</td>
            </tr>
    @endforeach

    {{-- Summary --}}
    <tr>
        <td colspan="{{ $splitLeft }}" class="cell-none">
            <div class="summary-label">{{ __('HSN/SAC Summary') }}</div>
            <table class="hsn-inner">
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
                            <td><span class="mono">{{ $row['hsn'] }}</span>@if ($row['tax_rate'] > 0)<span class="muted"> @ {{ $row['tax_rate'] + 0 }}%</span>@endif</td>
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
                    <tr>
                        <td>{{ __('Total') }}</td>
                        <td class="right mono">{{ \App\Support\Money::format($invoice->subtotal_amount) }}</td>
                        @if ($isInterState)
                            <td class="right mono">{{ \App\Support\Money::format($taxBreakdown['igst']) }}</td>
                        @else
                            <td class="right mono">{{ \App\Support\Money::format($taxBreakdown['cgst']) }}</td>
                            <td class="right mono">{{ \App\Support\Money::format($taxBreakdown['sgst']) }}</td>
                        @endif
                        <td class="right mono">{{ \App\Support\Money::format($invoice->tax_amount) }}</td>
                    </tr>
                </tbody>
            </table>
            <div class="amount-words-block">
                <span class="words-label">{{ __('Amount Chargeable (in words)') }}</span>
                {{ $amountInWords }}
            </div>
            @if ($invoice->notes)
                <div class="notes-line"><strong>{{ __('Notes') }}:</strong> {{ $invoice->notes }}</div>
            @endif
        </td>
        <td colspan="{{ $splitRight }}" class="cell-none cell-bg">
            <table class="totals-table">
                <tr>
                    <td class="muted">{{ __('Taxable Amount') }}</td>
                    <td class="right mono">{{ \App\Support\Money::format($invoice->subtotal_amount) }}</td>
                </tr>
                @if ($isInterState)
                    <tr>
                        <td class="muted">{{ __('IGST') }}</td>
                        <td class="right mono">{{ \App\Support\Money::format($taxBreakdown['igst']) }}</td>
                    </tr>
                @else
                    <tr>
                        <td class="muted">{{ __('CGST') }}</td>
                        <td class="right mono">{{ \App\Support\Money::format($taxBreakdown['cgst']) }}</td>
                    </tr>
                    <tr>
                        <td class="muted">{{ __('SGST') }}</td>
                        <td class="right mono">{{ \App\Support\Money::format($taxBreakdown['sgst']) }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="muted">{{ __('Total Tax') }}</td>
                    <td class="right mono">{{ \App\Support\Money::format($invoice->tax_amount) }}</td>
                </tr>
                <tr class="totals-grand">
                    <td>{{ __('Grand Total') }}</td>
                    <td class="right mono">{{ \App\Support\Money::format($invoice->total_amount) }}</td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Footer --}}
    <tr>
        <td colspan="{{ $splitLeft }}" class="cell-tight">
            @if ($businessBranding['bank_details'] ?? null)
                <div class="footer-block"><strong>{{ __('Bank Details') }}</strong><br>{{ $businessBranding['bank_details'] }}</div>
            @endif
            @if ($businessBranding['terms_and_conditions'] ?? null)
                <div class="footer-block">
                    <strong>{{ __('Terms & Conditions') }}</strong>
                    <div class="footer-text">{!! nl2br(e($businessBranding['terms_and_conditions'])) !!}</div>
                </div>
            @endif
            @if ($businessBranding['invoice_footer'] ?? null)
                <div class="footer-block footer-text">{!! nl2br(e($businessBranding['invoice_footer'])) !!}</div>
            @endif
        </td>
        <td colspan="{{ $splitRight }}" class="cell-tight">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">{{ $businessBranding['signature_label'] ?? __('For') }} {{ $business->legal_name ?: $business->name }}</div>
                <div class="signature-label">{{ __('Authorised Signatory') }}</div>
            </div>
        </td>
    </tr>

    <tr class="row-footer-note">
        <td colspan="{{ $splitLeft + $splitRight }}">
            {{ $footerText ?? __('This is a computer-generated tax invoice.') }}
            · {{ branding('product.name', config('app.name')) }}
        </td>
    </tr>
</table>
