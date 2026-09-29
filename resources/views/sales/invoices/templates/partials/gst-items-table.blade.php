@php
    $taxBreakdown = $taxBreakdown ?? \App\Support\GstTaxBreakdown::forInvoice($invoice, $business);
    $isInterState = $taxBreakdown['is_inter_state'];
@endphp

<table class="items continuation">
    <thead>
        <tr>
            <th rowspan="2" class="col-num">#</th>
            <th rowspan="2" class="desc">{{ __('Description of Goods / Services') }}</th>
            <th rowspan="2" class="col-hsn">HSN/SAC</th>
            <th rowspan="2" class="col-qty">{{ __('Qty') }}</th>
            <th rowspan="2" class="col-rate">{{ __('Rate') }}</th>
            <th rowspan="2" class="col-taxable">{{ __('Taxable') }}</th>
            @if ($isInterState)
                <th colspan="2">{{ __('IGST') }}</th>
            @else
                <th colspan="2">{{ __('CGST') }}</th>
                <th colspan="2">{{ __('SGST') }}</th>
            @endif
            <th rowspan="2" class="col-total">{{ __('Total') }}</th>
        </tr>
        <tr class="subhead">
            @if ($isInterState)
                <th class="col-pct">%</th>
                <th class="col-amt">{{ __('Amt') }}</th>
            @else
                <th class="col-pct">%</th>
                <th class="col-amt">{{ __('Amt') }}</th>
                <th class="col-pct">%</th>
                <th class="col-amt">{{ __('Amt') }}</th>
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
    </tbody>
</table>
