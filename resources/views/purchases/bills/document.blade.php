<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $bill->bill_number }} — {{ $business->name }}</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; background: #fff; color: #0f172a; margin: 0; padding: 2rem; }
        .toolbar { margin-bottom: 1.5rem; display: flex; justify-content: space-between; gap: 1rem; }
        .toolbar a, .toolbar button { font-size: 0.875rem; }
        .toolbar button { background: #4f46e5; color: #fff; border: 0; border-radius: 0.375rem; padding: 0.5rem 1rem; cursor: pointer; }
        .toolbar a { color: #4f46e5; text-decoration: none; }
        .header { display: flex; justify-content: space-between; gap: 1.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 1.5rem; margin-bottom: 2rem; }
        .title { font-size: 1.5rem; font-weight: 700; margin: 0; }
        .muted { color: #64748b; font-size: 0.875rem; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; margin-bottom: 2rem; }
        th, td { padding: 0.5rem 0; border-bottom: 1px solid #f1f5f9; }
        th { text-align: left; font-weight: 600; color: #334155; border-bottom: 2px solid #cbd5e1; }
        .right { text-align: right; }
        .mono { font-family: ui-monospace, monospace; }
        .totals { margin-left: auto; max-width: 18rem; font-size: 0.875rem; }
        .totals div { display: flex; justify-content: space-between; padding: 0.25rem 0; }
        .totals .grand { border-top: 1px solid #cbd5e1; margin-top: 0.5rem; padding-top: 0.5rem; font-size: 1rem; font-weight: 600; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
        @media screen and (max-width: 767px) {
            body { padding: 1rem; overflow-x: hidden; }
            .toolbar, .header { flex-direction: column; align-items: flex-start; }
            .grid { grid-template-columns: 1fr; }
            .right { text-align: left; }
            .totals { margin-left: 0; max-width: 100%; }
        }
    </style>
</head>
<body>
    @if ($showToolbar ?? false)
        <div class="toolbar no-print">
            <a href="{{ route('purchases.bills.index') }}">&larr; {{ __('Back to bills') }}</a>
            <div style="display:flex;gap:0.5rem;">
                <a href="{{ route('purchases.bills.download', $bill) }}">{{ __('Download') }}</a>
                <button type="button" onclick="window.print()">{{ __('Print / Save as PDF') }}</button>
            </div>
        </div>
    @endif

    <div style="max-width: 48rem; margin: 0 auto;">
        <div class="header">
            <div>
                <h1 class="title">{{ $business->name }}</h1>
                @if ($business->gstin)
                    <p class="muted">GSTIN: {{ $business->gstin }}</p>
                @endif
                @if ($business->state)
                    <p class="muted">{{ $business->state }}</p>
                @endif
            </div>
            <div class="right">
                <p style="font-size:1.125rem;font-weight:600;margin:0;">{{ __('Purchase Bill') }}</p>
                <p class="mono">{{ $bill->bill_number }}</p>
                <p class="muted">{{ $bill->bill_date->format('d M Y') }}</p>
            </div>
        </div>

        <div class="grid">
            <div>
                <p class="muted" style="font-weight:600;text-transform:uppercase;letter-spacing:0.05em;font-size:0.75rem;">{{ __('Supplier') }}</p>
                <p style="font-weight:600;margin:0.25rem 0 0;">{{ $bill->party->name }}</p>
                @if ($bill->party->gstin)
                    <p class="muted">GSTIN: {{ $bill->party->gstin }}</p>
                @endif
                @if ($bill->party->phone)
                    <p class="muted">{{ $bill->party->phone }}</p>
                @endif
            </div>
            <div class="right muted">
                <p>{{ __('Settlement') }}: {{ $bill->settlement_mode->isCredit() ? __('Credit') : __('Cash / Bank') }}</p>
                @if ($bill->reference)
                    <p>{{ __('Ref') }}: {{ $bill->reference }}</p>
                @endif
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>{{ __('Description') }}</th>
                    <th class="right">{{ __('Qty') }}</th>
                    <th class="right">{{ __('Rate') }}</th>
                    <th class="right">{{ __('Tax') }}</th>
                    <th class="right">{{ __('Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bill->lines as $line)
                    <tr>
                        <td>
                            {{ $line->description }}
                            @if ($line->hsn_sac)
                                <span class="muted" style="display:block;font-size:0.75rem;">HSN/SAC: {{ $line->hsn_sac }}</span>
                            @endif
                        </td>
                        <td class="right mono">{{ $line->quantity + 0 }}</td>
                        <td class="right mono">{{ \App\Support\Money::format($line->unit_price) }}</td>
                        <td class="right muted">{{ $line->tax_rate }}%</td>
                        <td class="right mono">{{ \App\Support\Money::format($line->line_total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div><span class="muted">{{ __('Subtotal') }}</span><span class="mono">{{ \App\Support\Money::format($bill->subtotal_amount) }}</span></div>
            <div><span class="muted">{{ __('Tax') }}</span><span class="mono">{{ \App\Support\Money::format($bill->tax_amount) }}</span></div>
            <div class="grand"><span>{{ __('Total') }}</span><span class="mono">{{ \App\Support\Money::format($bill->total_amount) }}</span></div>
        </div>

        @if ($bill->notes)
            <div style="margin-top:2rem;border-top:1px solid #e2e8f0;padding-top:1rem;">
                <p style="font-weight:600;margin:0 0 0.25rem;">{{ __('Notes') }}</p>
                <p class="muted">{{ $bill->notes }}</p>
            </div>
        @endif
    </div>

    @if (request()->boolean('autoprint'))
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
