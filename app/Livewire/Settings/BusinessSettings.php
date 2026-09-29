<?php

namespace App\Livewire\Settings;

use App\Application\Services\BusinessBrandingService;
use App\Application\Services\BusinessService;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\Business;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class BusinessSettings extends Component
{
    use DispatchesToasts;
    use WithFileUploads;

    public string $name = '';

    public string $legalName = '';

    public string $email = '';

    public string $phone = '';

    public string $gstin = '';

    public string $pan = '';

    public string $addressLine1 = '';

    public string $addressLine2 = '';

    public string $city = '';

    public string $state = '';

    public string $postalCode = '';

    public string $timezone = 'Asia/Kolkata';

    public $logo = null;

    public ?string $existingLogoUrl = null;

    public string $termsAndConditions = '';

    public string $invoiceFooter = '';

    public string $bankName = '';

    public string $bankAccount = '';

    public string $bankIfsc = '';

    public string $upiId = '';

    public string $signatureLabel = '';

    public string $website = '';

    public function mount(BusinessBrandingService $brandingService): void
    {
        $business = business_context()->business();
        abort_if($business === null, 404);

        $this->authorize('update', $business);

        $this->fillFromBusiness($business, $brandingService);
    }

    public function save(BusinessService $businessService, BusinessBrandingService $brandingService): void
    {
        $business = business_context()->business();
        abort_if($business === null, 404);

        $this->authorize('update', $business);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'legalName' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'gstin' => ['nullable', 'string', 'size:15'],
            'pan' => ['nullable', 'string', 'size:10'],
            'addressLine1' => ['nullable', 'string', 'max:255'],
            'addressLine2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postalCode' => ['nullable', 'string', 'max:20'],
            'timezone' => ['required', 'string', 'max:64'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'termsAndConditions' => ['nullable', 'string', 'max:5000'],
            'invoiceFooter' => ['nullable', 'string', 'max:1000'],
            'bankName' => ['nullable', 'string', 'max:255'],
            'bankAccount' => ['nullable', 'string', 'max:50'],
            'bankIfsc' => ['nullable', 'string', 'max:20'],
            'upiId' => ['nullable', 'string', 'max:100'],
            'signatureLabel' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'string', 'max:255'],
        ]);

        $businessService->updateProfile($business, [
            'name' => $validated['name'],
            'legal_name' => $validated['legalName'] ?: $validated['name'],
            'email' => $validated['email'] ?: null,
            'phone' => $validated['phone'] ?: null,
            'gstin' => $validated['gstin'] ?: null,
            'pan' => $validated['pan'] ?: null,
            'address_line_1' => $validated['addressLine1'] ?: null,
            'address_line_2' => $validated['addressLine2'] ?: null,
            'city' => $validated['city'] ?: null,
            'state' => $validated['state'] ?: null,
            'postal_code' => $validated['postalCode'] ?: null,
            'timezone' => $validated['timezone'],
        ]);

        $brandingService->update($business, [
            'terms_and_conditions' => $validated['termsAndConditions'],
            'invoice_footer' => $validated['invoiceFooter'],
            'bank_name' => $validated['bankName'],
            'bank_account' => $validated['bankAccount'],
            'bank_ifsc' => $validated['bankIfsc'],
            'upi_id' => $validated['upiId'],
            'signature_label' => $validated['signatureLabel'],
            'website' => $validated['website'],
        ], $validated['logo'] ?? null);

        $this->toastSuccess(__('Company settings saved.'));

        $this->logo = null;
        $this->fillFromBusiness($business->fresh(), $brandingService);
        $this->dispatch('branding-updated');
    }

    public function removeLogo(BusinessBrandingService $brandingService): void
    {
        $business = business_context()->business();
        abort_if($business === null, 404);

        $this->authorize('update', $business);

        $brandingService->removeLogo($business);

        $this->logo = null;
        $this->existingLogoUrl = null;

        $this->toastSuccess(__('Company logo removed.'));
        $this->dispatch('branding-updated');
    }

    public function render(): View
    {
        return view('livewire.settings.business-settings');
    }

    private function fillFromBusiness(Business $business, BusinessBrandingService $brandingService): void
    {
        $this->name = $business->name;
        $this->legalName = $business->legal_name ?? '';
        $this->email = $business->email ?? '';
        $this->phone = $business->phone ?? '';
        $this->gstin = $business->gstin ?? '';
        $this->pan = $business->pan ?? '';
        $this->addressLine1 = $business->address_line_1 ?? '';
        $this->addressLine2 = $business->address_line_2 ?? '';
        $this->city = $business->city ?? '';
        $this->state = $business->state ?? '';
        $this->postalCode = $business->postal_code ?? '';
        $this->timezone = $business->timezone ?? 'Asia/Kolkata';

        $branding = $brandingService->forBusiness($business);
        $rawBranding = ($business->settings ?? [])['branding'] ?? [];
        $this->existingLogoUrl = $branding['logo_url'];
        $this->termsAndConditions = $branding['terms_and_conditions'] ?? '';
        $this->invoiceFooter = $branding['invoice_footer'] ?? '';
        $this->bankName = $branding['bank_name'] ?? '';
        $this->bankAccount = $branding['bank_account'] ?? '';
        $this->bankIfsc = $branding['bank_ifsc'] ?? '';
        $this->upiId = $branding['upi_id'] ?? '';
        $this->signatureLabel = $rawBranding['signature_label'] ?? '';
        $this->website = $branding['website'] ?? '';
    }
}
