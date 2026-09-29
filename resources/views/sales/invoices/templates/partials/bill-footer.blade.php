<footer class="bill-footer">
    <div class="bill-footer-grid">
        <div>
            @if ($businessBranding['bank_details'] ?? null)
                <p class="footer-block"><strong>{{ __('Bank Details') }}</strong><br>{{ $businessBranding['bank_details'] }}</p>
            @endif
            @if ($businessBranding['terms_and_conditions'] ?? null)
                <div class="footer-block">
                    <strong>{{ __('Terms & Conditions') }}</strong>
                    <p class="footer-text">{!! nl2br(e($businessBranding['terms_and_conditions'])) !!}</p>
                </div>
            @endif
            @if ($businessBranding['invoice_footer'] ?? null)
                <p class="footer-block footer-text">{!! nl2br(e($businessBranding['invoice_footer'])) !!}</p>
            @endif
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <p class="signature-label">{{ $businessBranding['signature_label'] ?? __('For') }} {{ $business->legal_name ?: $business->name }}</p>
            <p class="signature-label">{{ __('Authorised Signatory') }}</p>
        </div>
    </div>
    <p class="bill-footnote">
        {{ $footerText ?? __('This is a computer-generated bill.') }}
        · {{ branding('product.name', config('app.name')) }}
    </p>
</footer>
