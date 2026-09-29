<?php

namespace App\Application\Services;

use App\Domain\Business\BusinessBranding;
use App\Models\Business;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class BusinessBrandingService
{
    /**
     * @return array{
     *     logo_path: ?string,
     *     logo_url: ?string,
     *     terms_and_conditions: ?string,
     *     invoice_footer: ?string,
     *     bank_name: ?string,
     *     bank_account: ?string,
     *     bank_ifsc: ?string,
     *     upi_id: ?string,
     *     signature_label: ?string,
     *     website: ?string,
     *     bank_details: ?string
     * }
     */
    public function forBusiness(Business $business): array
    {
        $branding = $this->raw($business);
        $logoPath = $branding[BusinessBranding::LOGO_PATH] ?? null;

        return [
            'logo_path' => is_string($logoPath) ? $logoPath : null,
            'logo_url' => $this->logoUrl($business),
            'terms_and_conditions' => $this->stringOrNull($branding[BusinessBranding::TERMS_AND_CONDITIONS] ?? null),
            'invoice_footer' => $this->stringOrNull($branding[BusinessBranding::INVOICE_FOOTER] ?? null),
            'bank_name' => $this->stringOrNull($branding[BusinessBranding::BANK_NAME] ?? null),
            'bank_account' => $this->stringOrNull($branding[BusinessBranding::BANK_ACCOUNT] ?? null),
            'bank_ifsc' => $this->stringOrNull($branding[BusinessBranding::BANK_IFSC] ?? null),
            'upi_id' => $this->stringOrNull($branding[BusinessBranding::UPI_ID] ?? null),
            'signature_label' => $this->stringOrNull($branding[BusinessBranding::SIGNATURE_LABEL] ?? null),
            'website' => $this->stringOrNull($branding[BusinessBranding::WEBSITE] ?? null),
            'bank_details' => $this->formatBankDetails($branding),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Business $business, array $attributes, TemporaryUploadedFile|UploadedFile|null $logo = null): Business
    {
        $settings = $business->settings ?? [];
        $branding = $settings['branding'] ?? [];

        foreach ([
            BusinessBranding::TERMS_AND_CONDITIONS => 'terms_and_conditions',
            BusinessBranding::INVOICE_FOOTER => 'invoice_footer',
            BusinessBranding::BANK_NAME => 'bank_name',
            BusinessBranding::BANK_ACCOUNT => 'bank_account',
            BusinessBranding::BANK_IFSC => 'bank_ifsc',
            BusinessBranding::UPI_ID => 'upi_id',
            BusinessBranding::SIGNATURE_LABEL => 'signature_label',
            BusinessBranding::WEBSITE => 'website',
        ] as $storageKey => $inputKey) {
            if (array_key_exists($inputKey, $attributes)) {
                $value = $attributes[$inputKey];
                $branding[$storageKey] = is_string($value) && trim($value) !== '' ? trim($value) : null;
            }
        }

        if ($logo !== null) {
            $this->deleteLogoFile($branding[BusinessBranding::LOGO_PATH] ?? null);
            $branding[BusinessBranding::LOGO_PATH] = $logo->store(
                'business-logos/'.$business->id,
                'public',
            );
        }

        $settings['branding'] = $branding;

        $business->update(['settings' => $settings]);

        return $business->fresh();
    }

    public function removeLogo(Business $business): Business
    {
        $settings = $business->settings ?? [];
        $branding = $settings['branding'] ?? [];

        $this->deleteLogoFile($branding[BusinessBranding::LOGO_PATH] ?? null);
        unset($branding[BusinessBranding::LOGO_PATH]);

        $settings['branding'] = $branding;
        $business->update(['settings' => $settings]);

        return $business->fresh();
    }

    public function logoUrl(Business $business): ?string
    {
        $path = $this->raw($business)[BusinessBranding::LOGO_PATH] ?? null;

        if (! is_string($path) || $path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return url(Storage::disk('public')->url($path));
    }

    /**
     * @return array<string, mixed>
     */
    private function raw(Business $business): array
    {
        return ($business->settings ?? [])['branding'] ?? [];
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    /**
     * @param  array<string, mixed>  $branding
     */
    private function formatBankDetails(array $branding): ?string
    {
        $parts = collect([
            $this->stringOrNull($branding[BusinessBranding::BANK_NAME] ?? null),
            $this->stringOrNull($branding[BusinessBranding::BANK_ACCOUNT] ?? null)
                ? __('A/C').': '.$this->stringOrNull($branding[BusinessBranding::BANK_ACCOUNT] ?? null)
                : null,
            $this->stringOrNull($branding[BusinessBranding::BANK_IFSC] ?? null)
                ? 'IFSC: '.$this->stringOrNull($branding[BusinessBranding::BANK_IFSC] ?? null)
                : null,
            $this->stringOrNull($branding[BusinessBranding::UPI_ID] ?? null)
                ? 'UPI: '.$this->stringOrNull($branding[BusinessBranding::UPI_ID] ?? null)
                : null,
        ])->filter()->values();

        if ($parts->isEmpty()) {
            return null;
        }

        return $parts->implode(' · ');
    }

    private function deleteLogoFile(?string $path): void
    {
        if (is_string($path) && $path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
