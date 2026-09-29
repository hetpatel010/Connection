@if (($businessBranding['logo_url'] ?? null) || ($businessBranding['website'] ?? null))
    <div class="branding-header">
        @if ($businessBranding['logo_url'] ?? null)
            <img src="{{ $businessBranding['logo_url'] }}" alt="{{ $business->name }}" class="branding-logo">
        @endif
        @if ($businessBranding['website'] ?? null)
            <p class="muted branding-website">{{ $businessBranding['website'] }}</p>
        @endif
    </div>
@endif
