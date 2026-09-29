<?php

use App\Application\Services\BusinessContext;
use App\Application\Services\InvoiceDocumentService;
use App\Application\Services\InvoiceTemplateService;
use App\Application\Services\SalesInvoiceService;
use App\Domain\Invoicing\InvoiceTemplateRegistry;
use App\Domain\Transactions\Enums\BillingType;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Livewire\Actions\Logout;
use App\Livewire\Sales\CreateInvoice;
use App\Livewire\Settings\InvoiceTemplatesSettings;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

/**
 * Saving a template on the invoice templates page is a permanent business-level
 * default: every invoice created afterwards carries it, and changing it later
 * only reaches invoices created after the change.
 */
function saveInvoiceTemplateDefaults(User $user, string $gstTemplate, string $nonGstTemplate): void
{
    Livewire::actingAs($user)
        ->test(InvoiceTemplatesSettings::class)
        ->set('defaultTemplateGst', $gstTemplate)
        ->set('defaultTemplateNonGst', $nonGstTemplate)
        ->call('save')
        ->assertHasNoErrors();
}

/**
 * Create one invoice through the form the way a user does, so the template the
 * form pre-selects is the template that ends up stored on the invoice.
 */
function createInvoiceWithSavedTemplate(User $user, Party $customer, BillingType $billingType): SalesInvoice
{
    Livewire::actingAs($user)
        ->test(CreateInvoice::class)
        ->set('partyId', $customer->id)
        ->set('billingType', $billingType->value)
        ->set('lines.0.description', 'Consulting')
        ->set('lines.0.quantity', '1')
        ->set('lines.0.unit_price', '1000')
        ->set('lines.0.tax_rate', '18')
        ->call('saveDraft')
        ->assertHasNoErrors();

    return SalesInvoice::query()->orderByDesc('invoice_number')->firstOrFail();
}

test('a saved default survives logging out and back in', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();

    saveInvoiceTemplateDefaults($user, InvoiceTemplateRegistry::MODERN_GST, InvoiceTemplateRegistry::MINIMAL_BILL);

    $this->actingAs($user);

    // The same action the app shell invokes, which drops the session, and the
    // same reset a fresh request performs, so nothing of the previous sign-in
    // is left in session or in the resolved business context.
    (new Logout)();
    app(BusinessContext::class)->clear();

    Auth::attempt(['email' => $user->email, 'password' => 'password']);

    $this->actingAs($user)
        ->withSession([BusinessContext::BUSINESS_SESSION_KEY => $business->id])
        ->get(route('settings.invoice-templates'))
        ->assertOk();

    // Re-resolved from scratch, so the selection here can only have come from
    // the persisted setting rather than from session state.
    Livewire::actingAs($user)
        ->test(InvoiceTemplatesSettings::class)
        ->assertSet('defaultTemplateGst', InvoiceTemplateRegistry::MODERN_GST)
        ->assertSet('defaultTemplateNonGst', InvoiceTemplateRegistry::MINIMAL_BILL);

    expect(createInvoiceWithSavedTemplate($user, $customer, BillingType::Gst)->document_template)
        ->toBe(InvoiceTemplateRegistry::MODERN_GST);
});

test('a saved default is still selected when the templates page is opened again', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    saveInvoiceTemplateDefaults($user, InvoiceTemplateRegistry::CLASSIC_GST, InvoiceTemplateRegistry::CLASSIC_BILL);

    // A fresh mount reads the stored value, so the selection survives a page
    // refresh and a later sign-in instead of reverting to the built-in default.
    Livewire::actingAs($user)
        ->test(InvoiceTemplatesSettings::class)
        ->assertSet('defaultTemplateGst', InvoiceTemplateRegistry::CLASSIC_GST)
        ->assertSet('defaultTemplateNonGst', InvoiceTemplateRegistry::CLASSIC_BILL);

    expect(app(InvoiceTemplateService::class)->defaultTemplate($business->fresh(), BillingType::Gst))
        ->toBe(InvoiceTemplateRegistry::CLASSIC_GST)
        ->and(app(InvoiceTemplateService::class)->defaultTemplate($business->fresh(), BillingType::NonGst))
        ->toBe(InvoiceTemplateRegistry::CLASSIC_BILL);
});

test('a default saved for one business does not become another business default', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    saveInvoiceTemplateDefaults($user, InvoiceTemplateRegistry::MODERN_GST, InvoiceTemplateRegistry::MINIMAL_BILL);

    ['user' => $otherUser, 'business' => $otherBusiness] = $this->createUserWithBusiness();
    $otherCustomer = Party::factory()->customer()->forBusiness($otherBusiness)->create();

    expect(createInvoiceWithSavedTemplate($otherUser, $otherCustomer, BillingType::Gst)->document_template)
        ->toBe(InvoiceTemplateRegistry::defaultFor(BillingType::Gst));

    app(BusinessContext::class)->setBusiness($business, $user);
    $customer = Party::factory()->customer()->forBusiness($business)->create();

    expect(createInvoiceWithSavedTemplate($user, $customer, BillingType::Gst)->document_template)
        ->toBe(InvoiceTemplateRegistry::MODERN_GST);
});

test('the new invoice page opens on the saved default for the chosen billing type', function () {
    ['user' => $user] = $this->createUserWithBusiness();

    saveInvoiceTemplateDefaults($user, InvoiceTemplateRegistry::MODERN_GST, InvoiceTemplateRegistry::MINIMAL_BILL);

    Livewire::actingAs($user)
        ->test(CreateInvoice::class)
        ->assertSet('documentTemplate', InvoiceTemplateRegistry::MODERN_GST)
        ->set('billingType', 'non_gst')
        ->assertSet('documentTemplate', InvoiceTemplateRegistry::MINIMAL_BILL)
        ->set('billingType', 'gst')
        ->assertSet('documentTemplate', InvoiceTemplateRegistry::MODERN_GST);
});

test('every new invoice carries the saved default until the default is changed', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();

    saveInvoiceTemplateDefaults($user, InvoiceTemplateRegistry::CLASSIC_GST, InvoiceTemplateRegistry::CLASSIC_BILL);

    $first = createInvoiceWithSavedTemplate($user, $customer, BillingType::Gst);
    $second = createInvoiceWithSavedTemplate($user, $customer, BillingType::Gst);
    $third = createInvoiceWithSavedTemplate($user, $customer, BillingType::Gst);

    saveInvoiceTemplateDefaults($user, InvoiceTemplateRegistry::MODERN_GST, InvoiceTemplateRegistry::MINIMAL_BILL);

    $fourth = createInvoiceWithSavedTemplate($user, $customer, BillingType::Gst);

    expect($first->document_template)->toBe(InvoiceTemplateRegistry::CLASSIC_GST)
        ->and($second->document_template)->toBe(InvoiceTemplateRegistry::CLASSIC_GST)
        ->and($third->document_template)->toBe(InvoiceTemplateRegistry::CLASSIC_GST)
        ->and($fourth->document_template)->toBe(InvoiceTemplateRegistry::MODERN_GST);
});

test('invoices created before a default change still print with their own template', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create([
        'billing_state' => $business->state,
        'gstin' => '24AABCU9603R1ZM',
    ]);

    saveInvoiceTemplateDefaults($user, InvoiceTemplateRegistry::CLASSIC_GST, InvoiceTemplateRegistry::CLASSIC_BILL);

    $existing = createInvoiceWithSavedTemplate($user, $customer, BillingType::Gst);

    saveInvoiceTemplateDefaults($user, InvoiceTemplateRegistry::MODERN_GST, InvoiceTemplateRegistry::MINIMAL_BILL);

    $existing->refresh();

    expect($existing->document_template)->toBe(InvoiceTemplateRegistry::CLASSIC_GST)
        ->and(app(InvoiceDocumentService::class)->render($existing, $business))
        ->toContain('<table class="invoice-sheet classic">');
});

test('a new non-gst invoice uses the saved non-gst default', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();

    saveInvoiceTemplateDefaults($user, InvoiceTemplateRegistry::MODERN_GST, InvoiceTemplateRegistry::MINIMAL_BILL);

    $invoice = createInvoiceWithSavedTemplate($user, $customer, BillingType::NonGst);

    expect($invoice->document_template)->toBe(InvoiceTemplateRegistry::MINIMAL_BILL)
        ->and(app(InvoiceDocumentService::class)->render($invoice, $business))
        ->toContain('<div class="bill-minimal">');
});

test('a template chosen on the invoice overrides the saved default', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();

    saveInvoiceTemplateDefaults($user, InvoiceTemplateRegistry::MODERN_GST, InvoiceTemplateRegistry::MINIMAL_BILL);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
            'billing_type' => BillingType::Gst,
            'document_template' => InvoiceTemplateRegistry::CLASSIC_GST,
        ],
        lines: [
            ['description' => 'Consulting', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 18],
        ],
    );

    expect($invoice->document_template)->toBe(InvoiceTemplateRegistry::CLASSIC_GST);
});

test('a template from the other billing type is refused as a default', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    saveInvoiceTemplateDefaults($user, InvoiceTemplateRegistry::MODERN_GST, InvoiceTemplateRegistry::MINIMAL_BILL);

    Livewire::actingAs($user)
        ->test(InvoiceTemplatesSettings::class)
        ->set('defaultTemplateGst', InvoiceTemplateRegistry::MODERN_GST)
        ->set('defaultTemplateNonGst', InvoiceTemplateRegistry::MODERN_GST)
        ->call('save')
        ->assertHasErrors(['defaultTemplateNonGst']);

    expect(app(InvoiceTemplateService::class)->defaultTemplate($business->fresh(), BillingType::NonGst))
        ->toBe(InvoiceTemplateRegistry::MINIMAL_BILL);
});
