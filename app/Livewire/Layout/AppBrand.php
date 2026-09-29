<?php

namespace App\Livewire\Layout;

use App\Application\Services\BusinessBrandingService;
use App\Application\Services\BusinessContext;
use Livewire\Attributes\On;
use Livewire\Component;

class AppBrand extends Component
{
    public string $theme = 'dark';

    #[On('business-changed')]
    #[On('branding-updated')]
    public function refreshBrand(): void
    {
        //
    }

    public function render(BusinessContext $businessContext, BusinessBrandingService $brandingService)
    {
        $business = $businessContext->business();
        $logoUrl = null;
        $brandName = branding('product.name', config('app.name'));

        if ($business !== null) {
            $logoUrl = $brandingService->forBusiness($business)['logo_url'];
            $brandName = $business->legal_name ?: $business->name;
        }

        return view('livewire.layout.app-brand', [
            'logoUrl' => $logoUrl,
            'brandName' => $brandName,
            'initial' => strtoupper(substr($brandName, 0, 1)),
        ]);
    }
}
