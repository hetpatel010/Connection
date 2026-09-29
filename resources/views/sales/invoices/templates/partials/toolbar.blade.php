@if ($showToolbar ?? false)
    <div class="toolbar no-print">
        @if (($documentContext ?? 'sales') === 'purchase')
            <a href="{{ route('purchases.bills.index') }}">&larr; {{ __('Back to bills') }}</a>
            <div style="display:flex;gap:0.5rem;">
                <a href="{{ route('purchases.bills.download', $purchaseBill) }}">{{ __('Download HTML') }}</a>
                <a href="{{ route('purchases.bills.print', [$purchaseBill, 'autoprint' => 1]) }}">{{ __('Print / PDF') }}</a>
            </div>
        @else
            <a href="{{ route('sales.invoices.index') }}">&larr; {{ __('Back to invoices') }}</a>
            <div style="display:flex;gap:0.5rem;">
                <a href="{{ route('sales.invoices.download', $invoice) }}">{{ __('Download HTML') }}</a>
                <a href="{{ route('sales.invoices.print', [$invoice, 'autoprint' => 1]) }}">{{ __('Print / PDF') }}</a>
            </div>
        @endif
    </div>
@endif
