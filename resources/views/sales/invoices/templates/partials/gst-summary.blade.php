@php
    $taxBreakdown = $taxBreakdown ?? \App\Support\GstTaxBreakdown::forInvoice($invoice, $business);
    $isInterState = $taxBreakdown['is_inter_state'];
    $amountInWords = $amountInWords ?? \App\Support\IndianAmountInWords::fromPaise((int) $invoice->total_amount);
@endphp

<table class="inv-grid continuation">
    <tr>
        <td class="cell-pad" style="width:68%; vertical-align:top;">
            <table class="hsn-table">
                <thead>
                    <tr>
                        <th>{{ __('HSN/SAC Summary') }}</th>
                        <th class="right">{{ __('Taxable') }}</th>
                        @if ($isInterState)
                            <th class="right">IGST</th>
                        @else
                            <th class="right">CGST</th>
                            <th class="right">SGST</th>
                        @endif
                        <th class="right">{{ __('Total Tax') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($taxBreakdown['hsn_summary'] as $row)
                        <tr>
                            <td>
                                <span class="mono">{{ $row['hsn'] }}</span>
                                @if ($row['tax_rate'] > 0)
                                    <span class="muted"> @ {{ $row['tax_rate'] + 0 }}%</span>
                                @endif
                            </td>
                            <td class="right mono">{{ \App\Support\Money::format($row['taxable']) }}</td>
                            @if ($isInterState)
                                <td class="right mono">{{ \App\Support\Money::format($row['igst']) }}</td>
                            @else
                                <td class="right mono">{{ \App\Support\Money::format($row['cgst']) }}</td>
                                <td class="right mono">{{ \App\Support\Money::format($row['sgst']) }}</td>
                            @endif
                            <td class="right mono">{{ \App\Support\Money::format($row['total_tax']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isInterState ? 4 : 5 }}" class="center muted">{{ __('No tax lines') }}</td>
                        </tr>
                    @endforelse
                    <tr class="total-row">
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

            <div class="amount-words">
                <strong>{{ __('Amount Chargeable (in words)') }}</strong>
                {{ $amountInWords }}
            </div>

            @if ($invoice->notes)
                <p class="notes-line"><strong>{{ __('Notes') }}:</strong> {{ $invoice->notes }}</p>
            @endif
        </td>
        <td class="cell-pad no-border-left" style="width:32%; vertical-align:top; background:#fafbfc;">
            <div class="totals-box">
                <div class="totals-row">
                    <span class="totals-label">{{ __('Taxable Amount') }}</span>
                    <span class="mono">{{ \App\Support\Money::format($invoice->subtotal_amount) }}</span>
                </div>
                @if ($isInterState)
                    <div class="totals-row">
                        <span class="totals-label">{{ __('IGST') }}</span>
                        <span class="mono">{{ \App\Support\Money::format($taxBreakdown['igst']) }}</span>
                    </div>
                @else
                    <div class="totals-row">
                        <span class="totals-label">{{ __('CGST') }}</span>
                        <span class="mono">{{ \App\Support\Money::format($taxBreakdown['cgst']) }}</span>
                    </div>
                    <div class="totals-row">
                        <span class="totals-label">{{ __('SGST') }}</span>
                        <span class="mono">{{ \App\Support\Money::format($taxBreakdown['sgst']) }}</span>
                    </div>
                @endif
                <div class="totals-row">
                    <span class="totals-label">{{ __('Total Tax') }}</span>
                    <span class="mono">{{ \App\Support\Money::format($invoice->tax_amount) }}</span>
                </div>
                <div class="totals-row grand">
                    <span class="totals-label">{{ __('Grand Total') }}</span>
                    <span class="mono">{{ \App\Support\Money::format($invoice->total_amount) }}</span>
                </div>
            </div>
        </td>
    </tr>
</table>

@include('sales.invoices.templates.partials.footer')
