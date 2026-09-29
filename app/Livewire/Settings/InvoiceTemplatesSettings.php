<?php

namespace App\Livewire\Settings;

use App\Application\Services\InvoiceTemplateService;
use App\Domain\Invoicing\InvoiceTemplateRegistry;
use App\Domain\Transactions\Enums\BillingType;
use App\Livewire\Concerns\DispatchesToasts;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class InvoiceTemplatesSettings extends Component
{
    use DispatchesToasts;

    public string $defaultTemplateGst = '';

    public string $defaultTemplateNonGst = '';

    public function mount(InvoiceTemplateService $invoiceTemplateService): void
    {
        $business = business_context()->business();
        abort_if($business === null, 404);

        $this->authorize('update', $business);

        $this->defaultTemplateGst = $invoiceTemplateService->defaultTemplate($business, BillingType::Gst);
        $this->defaultTemplateNonGst = $invoiceTemplateService->defaultTemplate($business, BillingType::NonGst);
    }

    public function save(InvoiceTemplateService $invoiceTemplateService): void
    {
        $business = business_context()->business();
        abort_if($business === null, 404);

        $this->authorize('update', $business);

        $validated = $this->validate([
            'defaultTemplateGst' => ['required', 'string', 'in:'.implode(',', InvoiceTemplateRegistry::slugsFor(BillingType::Gst))],
            'defaultTemplateNonGst' => ['required', 'string', 'in:'.implode(',', InvoiceTemplateRegistry::slugsFor(BillingType::NonGst))],
        ]);

        $invoiceTemplateService->setDefaultTemplate($business, BillingType::Gst, $validated['defaultTemplateGst']);
        $business = $invoiceTemplateService->setDefaultTemplate($business, BillingType::NonGst, $validated['defaultTemplateNonGst']);

        $this->defaultTemplateGst = $invoiceTemplateService->defaultTemplate($business, BillingType::Gst);
        $this->defaultTemplateNonGst = $invoiceTemplateService->defaultTemplate($business, BillingType::NonGst);

        $this->toastSuccess(__('Invoice template defaults saved.'));
    }

    public function render(InvoiceTemplateService $invoiceTemplateService): View
    {
        return view('livewire.settings.invoice-templates-settings', [
            'gstTemplates' => $invoiceTemplateService->optionsFor(BillingType::Gst),
            'nonGstTemplates' => $invoiceTemplateService->optionsFor(BillingType::NonGst),
        ]);
    }
}
