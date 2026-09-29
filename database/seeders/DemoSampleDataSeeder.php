<?php

namespace Database\Seeders;

use App\Application\Services\BusinessContext;
use App\Application\Services\BusinessService;
use App\Application\Services\ExpenseService;
use App\Application\Services\PaymentService;
use App\Application\Services\PurchaseBillService;
use App\Application\Services\SalesInvoiceService;
use App\Domain\Parties\Enums\PartyType;
use App\Domain\Shared\Enums\RecordStatus;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Models\Account;
use App\Models\Business;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SalesInvoice;
use App\Models\Unit;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Seeder;

class DemoSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        if (business_context()->hasBusiness()) {
            $business = business_context()->business();
            $owner = auth()->user();

            if ($business === null || $owner === null) {
                return;
            }
        } else {
            $owner = User::query()->where('email', 'owner@demo.test')->first();

            if ($owner === null) {
                return;
            }

            $business = $owner->businesses()->first();

            if ($business === null) {
                return;
            }

            app(BusinessContext::class)->setBusiness($business, $owner);
        }

        if (SalesInvoice::query()->withoutGlobalScopes()->where('business_id', $business->id)->exists()) {
            return;
        }

        $financialYear = FinancialYear::query()
            ->withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('is_current', true)
            ->first();

        if ($financialYear === null) {
            return;
        }

        if (! business_context()->hasBusiness()) {
            app(BusinessContext::class)->setBusiness($business, $owner);
        }

        if ($business->is_demo) {
            app(BusinessService::class)->updateProfile($business, [
                'name' => 'Demo Traders Pvt Ltd',
                'legal_name' => 'Demo Traders Private Limited',
                'email' => 'accounts@demotraders.test',
                'phone' => '9876543210',
                'gstin' => '24AABCD1234A1Z5',
                'pan' => 'AABCD1234A',
                'address_line_1' => '101, Ashram Road',
                'city' => 'Ahmedabad',
                'state' => 'Gujarat',
                'country' => 'IN',
                'postal_code' => '380009',
            ]);
        }

        $cashAccount = $this->accountByCode($business, '1100');
        $rentAccount = $this->accountByCode($business, '5300');
        $utilitiesAccount = $this->accountByCode($business, '5500');

        $customers = $this->seedCustomers($business);
        $suppliers = $this->seedSuppliers($business);
        $products = $this->seedProducts($business);

        $salesService = app(SalesInvoiceService::class);
        $purchaseService = app(PurchaseBillService::class);
        $paymentService = app(PaymentService::class);
        $expenseService = app(ExpenseService::class);

        $today = now()->startOfDay();

        $todayCashInvoice = $salesService->confirm($salesService->createDraft(
            business: $business,
            financialYear: $financialYear,
            user: $owner,
            attributes: [
                'party_id' => $customers['patel']->id,
                'invoice_date' => $today->toDateString(),
                'settlement_mode' => SettlementMode::Cash,
                'payment_account_id' => $cashAccount->id,
            ],
            lines: [
                ['product_id' => $products['mouse']->id, 'quantity' => 2, 'unit_price' => Money::rupeesToPaise(850), 'tax_rate' => 18],
                ['product_id' => $products['notebook']->id, 'quantity' => 5, 'unit_price' => Money::rupeesToPaise(120), 'tax_rate' => 12],
            ],
        ), $owner);

        $todayCreditInvoice = $salesService->confirm($salesService->createDraft(
            business: $business,
            financialYear: $financialYear,
            user: $owner,
            attributes: [
                'party_id' => $customers['shreeKrishna']->id,
                'invoice_date' => $today->toDateString(),
                'due_date' => $today->copy()->addDays(15)->toDateString(),
                'settlement_mode' => SettlementMode::Credit,
            ],
            lines: [
                ['product_id' => $products['phone']->id, 'quantity' => 3, 'unit_price' => Money::rupeesToPaise(12500), 'tax_rate' => 18],
            ],
        ), $owner);

        $olderCreditInvoice = $salesService->confirm($salesService->createDraft(
            business: $business,
            financialYear: $financialYear,
            user: $owner,
            attributes: [
                'party_id' => $customers['mehta']->id,
                'invoice_date' => $today->copy()->subDays(12)->toDateString(),
                'due_date' => $today->copy()->subDays(2)->toDateString(),
                'settlement_mode' => SettlementMode::Credit,
            ],
            lines: [
                ['product_id' => $products['rice']->id, 'quantity' => 10, 'unit_price' => Money::rupeesToPaise(650), 'tax_rate' => 5],
                ['product_id' => $products['oil']->id, 'quantity' => 8, 'unit_price' => Money::rupeesToPaise(180), 'tax_rate' => 5],
            ],
        ), $owner);

        $salesService->createDraft(
            business: $business,
            financialYear: $financialYear,
            user: $owner,
            attributes: [
                'party_id' => $customers['patel']->id,
                'invoice_date' => $today->toDateString(),
                'settlement_mode' => SettlementMode::Credit,
            ],
            lines: [
                ['product_id' => $products['bulb']->id, 'quantity' => 20, 'unit_price' => Money::rupeesToPaise(95), 'tax_rate' => 12],
            ],
        );

        $purchaseBill = $purchaseService->confirm($purchaseService->createDraft(
            business: $business,
            financialYear: $financialYear,
            user: $owner,
            attributes: [
                'party_id' => $suppliers['gujaratWholesale']->id,
                'bill_date' => $today->copy()->subDays(8)->toDateString(),
                'settlement_mode' => SettlementMode::Credit,
            ],
            lines: [
                ['product_id' => $products['phone']->id, 'quantity' => 5, 'unit_price' => Money::rupeesToPaise(9800), 'tax_rate' => 18],
                ['product_id' => $products['mouse']->id, 'quantity' => 10, 'unit_price' => Money::rupeesToPaise(620), 'tax_rate' => 18],
            ],
        ), $owner);

        $purchaseService->confirm($purchaseService->createDraft(
            business: $business,
            financialYear: $financialYear,
            user: $owner,
            attributes: [
                'party_id' => $suppliers['national']->id,
                'bill_date' => $today->copy()->subDays(5)->toDateString(),
                'settlement_mode' => SettlementMode::Credit,
            ],
            lines: [
                ['product_id' => $products['rice']->id, 'quantity' => 50, 'unit_price' => Money::rupeesToPaise(520), 'tax_rate' => 5],
                ['product_id' => $products['oil']->id, 'quantity' => 30, 'unit_price' => Money::rupeesToPaise(145), 'tax_rate' => 5],
            ],
        ), $owner);

        $paymentService->createAndConfirm(
            business: $business,
            financialYear: $financialYear,
            user: $owner,
            attributes: [
                'payment_type' => 'payment_in',
                'payment_date' => $today->copy()->subDays(3)->toDateString(),
                'party_id' => $customers['mehta']->id,
                'payment_mode' => 'cash',
                'account_id' => $cashAccount->id,
                'amount' => Money::rupeesToPaise(3000),
                'reference' => 'UPI-MEHTA-001',
                'allocations' => [
                    [
                        'allocatable_type' => 'sales_invoice',
                        'allocatable_id' => $olderCreditInvoice->id,
                        'allocated_amount' => Money::rupeesToPaise(3000),
                    ],
                ],
            ],
        );

        $paymentService->createAndConfirm(
            business: $business,
            financialYear: $financialYear,
            user: $owner,
            attributes: [
                'payment_type' => 'payment_out',
                'payment_date' => $today->copy()->subDays(2)->toDateString(),
                'party_id' => $suppliers['gujaratWholesale']->id,
                'payment_mode' => 'cash',
                'account_id' => $cashAccount->id,
                'amount' => Money::rupeesToPaise(25000),
                'reference' => 'NEFT-GWM-445',
                'allocations' => [
                    [
                        'allocatable_type' => 'purchase_bill',
                        'allocatable_id' => $purchaseBill->id,
                        'allocated_amount' => Money::rupeesToPaise(25000),
                    ],
                ],
            ],
        );

        $expenseService->createAndConfirm(
            business: $business,
            financialYear: $financialYear,
            user: $owner,
            attributes: [
                'expense_date' => $today->copy()->subDays(1)->toDateString(),
                'expense_account_id' => $rentAccount->id,
                'payment_account_id' => $cashAccount->id,
                'amount' => Money::rupeesToPaise(18000),
                'tax_amount' => 0,
                'description' => 'Shop rent - August 2026',
                'reference' => 'RENT-AUG-2026',
            ],
        );

        $expenseService->createAndConfirm(
            business: $business,
            financialYear: $financialYear,
            user: $owner,
            attributes: [
                'expense_date' => $today->toDateString(),
                'expense_account_id' => $utilitiesAccount->id,
                'payment_account_id' => $cashAccount->id,
                'amount' => Money::rupeesToPaise(2450),
                'tax_amount' => 0,
                'description' => 'Electricity bill',
                'reference' => 'MGVCL-88421',
            ],
        );

        unset($todayCashInvoice, $todayCreditInvoice, $olderCreditInvoice, $purchaseBill);
    }

    /**
     * @return array{shreeKrishna: Party, patel: Party, mehta: Party}
     */
    private function seedCustomers(Business $business): array
    {
        return [
            'shreeKrishna' => Party::query()->create([
                'business_id' => $business->id,
                'party_type' => PartyType::Customer,
                'name' => 'Shree Krishna Traders',
                'legal_name' => 'Shree Krishna Traders',
                'phone' => '9825012345',
                'email' => 'billing@shreekrishna.test',
                'gstin' => '24AAAAA0000A1Z5',
                'billing_address_line_1' => '12, Ring Road',
                'billing_city' => 'Rajkot',
                'billing_state' => 'Gujarat',
                'billing_country' => 'IN',
                'billing_postal_code' => '360001',
                'status' => RecordStatus::Active,
            ]),
            'patel' => Party::query()->create([
                'business_id' => $business->id,
                'party_type' => PartyType::Customer,
                'name' => 'Patel Electronics',
                'phone' => '9876011122',
                'email' => 'sales@patelelectronics.test',
                'billing_city' => 'Ahmedabad',
                'billing_state' => 'Gujarat',
                'billing_country' => 'IN',
                'status' => RecordStatus::Active,
            ]),
            'mehta' => Party::query()->create([
                'business_id' => $business->id,
                'party_type' => PartyType::Customer,
                'name' => 'Mehta General Stores',
                'phone' => '9898070605',
                'email' => 'store@mehtageneral.test',
                'billing_city' => 'Surat',
                'billing_state' => 'Gujarat',
                'billing_country' => 'IN',
                'status' => RecordStatus::Active,
            ]),
        ];
    }

    /**
     * @return array{gujaratWholesale: Party, national: Party}
     */
    private function seedSuppliers(Business $business): array
    {
        return [
            'gujaratWholesale' => Party::query()->create([
                'business_id' => $business->id,
                'party_type' => PartyType::Supplier,
                'name' => 'Gujarat Wholesale Mart',
                'legal_name' => 'Gujarat Wholesale Mart Pvt Ltd',
                'phone' => '7940012345',
                'email' => 'orders@gujaratwholesale.test',
                'gstin' => '24BBBBA0000B1Z5',
                'billing_city' => 'Ahmedabad',
                'billing_state' => 'Gujarat',
                'billing_country' => 'IN',
                'status' => RecordStatus::Active,
            ]),
            'national' => Party::query()->create([
                'business_id' => $business->id,
                'party_type' => PartyType::Supplier,
                'name' => 'National Grocery Suppliers',
                'phone' => '7940099887',
                'email' => 'supply@nationalgrocery.test',
                'billing_city' => 'Vadodara',
                'billing_state' => 'Gujarat',
                'billing_country' => 'IN',
                'status' => RecordStatus::Active,
            ]),
        ];
    }

    /**
     * @return array{phone: Product, mouse: Product, notebook: Product, rice: Product, oil: Product, bulb: Product, coke: Product}
     */
    private function seedProducts(Business $business): array
    {
        $pcsUnit = Unit::query()
            ->withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('symbol', 'Pcs')
            ->firstOrFail();

        $kgUnit = Unit::query()
            ->withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('symbol', 'Kg')
            ->firstOrFail();

        $ltrUnit = Unit::query()
            ->withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('symbol', 'Ltr')
            ->firstOrFail();

        $electronics = ProductCategory::query()->create([
            'business_id' => $business->id,
            'name' => 'Electronics',
            'description' => 'Mobile phones, accessories, and gadgets',
            'status' => RecordStatus::Active,
        ]);

        $grocery = ProductCategory::query()->create([
            'business_id' => $business->id,
            'name' => 'Grocery',
            'description' => 'Food grains, oil, and daily essentials',
            'status' => RecordStatus::Active,
        ]);

        $stationery = ProductCategory::query()->create([
            'business_id' => $business->id,
            'name' => 'Stationery',
            'description' => 'Office and school supplies',
            'status' => RecordStatus::Active,
        ]);

        return [
            'phone' => Product::query()->create([
                'business_id' => $business->id,
                'category_id' => $electronics->id,
                'unit_id' => $pcsUnit->id,
                'name' => 'Smartphone 128GB',
                'sku' => 'MOB-SM-128',
                'hsn_sac' => '8517',
                'brand' => 'DemoMobile',
                'purchase_price' => Money::rupeesToPaise(9800),
                'selling_price' => Money::rupeesToPaise(12500),
                'tax_rate' => 18,
                'opening_stock' => 15,
                'minimum_stock' => 3,
                'status' => RecordStatus::Active,
            ]),
            'mouse' => Product::query()->create([
                'business_id' => $business->id,
                'category_id' => $electronics->id,
                'unit_id' => $pcsUnit->id,
                'name' => 'Wireless Mouse',
                'sku' => 'ACC-MOUSE-WL',
                'hsn_sac' => '8471',
                'brand' => 'ClickPro',
                'purchase_price' => Money::rupeesToPaise(620),
                'selling_price' => Money::rupeesToPaise(850),
                'tax_rate' => 18,
                'opening_stock' => 40,
                'minimum_stock' => 10,
                'status' => RecordStatus::Active,
            ]),
            'bulb' => Product::query()->create([
                'business_id' => $business->id,
                'category_id' => $electronics->id,
                'unit_id' => $pcsUnit->id,
                'name' => 'LED Bulb 9W',
                'sku' => 'EL-BULB-9W',
                'hsn_sac' => '8539',
                'purchase_price' => Money::rupeesToPaise(70),
                'selling_price' => Money::rupeesToPaise(95),
                'tax_rate' => 12,
                'opening_stock' => 100,
                'status' => RecordStatus::Active,
            ]),
            'notebook' => Product::query()->create([
                'business_id' => $business->id,
                'category_id' => $stationery->id,
                'unit_id' => $pcsUnit->id,
                'name' => 'A4 Notebook (200 pages)',
                'sku' => 'STN-NB-A4',
                'hsn_sac' => '4820',
                'purchase_price' => Money::rupeesToPaise(85),
                'selling_price' => Money::rupeesToPaise(120),
                'tax_rate' => 12,
                'opening_stock' => 80,
                'status' => RecordStatus::Active,
            ]),
            'rice' => Product::query()->create([
                'business_id' => $business->id,
                'category_id' => $grocery->id,
                'unit_id' => $kgUnit->id,
                'name' => 'Basmati Rice',
                'sku' => 'GRC-RICE-BAS',
                'hsn_sac' => '1006',
                'purchase_price' => Money::rupeesToPaise(520),
                'selling_price' => Money::rupeesToPaise(650),
                'tax_rate' => 5,
                'opening_stock' => 200,
                'status' => RecordStatus::Active,
            ]),
            'oil' => Product::query()->create([
                'business_id' => $business->id,
                'category_id' => $grocery->id,
                'unit_id' => $ltrUnit->id,
                'name' => 'Sunflower Oil 1L',
                'sku' => 'GRC-OIL-1L',
                'hsn_sac' => '1512',
                'purchase_price' => Money::rupeesToPaise(145),
                'selling_price' => Money::rupeesToPaise(180),
                'tax_rate' => 5,
                'opening_stock' => 120,
                'status' => RecordStatus::Active,
            ]),
            'coke' => Product::query()->create([
                'business_id' => $business->id,
                'category_id' => $grocery->id,
                'unit_id' => $pcsUnit->id,
                'name' => 'Coke',
                'sku' => 'GRC-COKE-330',
                'hsn_sac' => '2202',
                'purchase_price' => Money::rupeesToPaise(35),
                'selling_price' => Money::rupeesToPaise(50),
                'tax_rate' => 18,
                'opening_stock' => 50,
                'status' => RecordStatus::Active,
            ]),
        ];
    }

    private function accountByCode(Business $business, string $code): Account
    {
        return Account::query()
            ->withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('code', $code)
            ->firstOrFail();
    }
}
