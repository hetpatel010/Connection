<?php

namespace Database\Seeders;

use App\Application\Services\BusinessContext;
use App\Domain\Inventory\Enums\StockDirection;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockLedgerSeeder extends Seeder
{
    public function run(): void
    {
        if (! business_context()->hasBusiness()) {
            $this->resolveDemoContext();
        }

        $business = business_context()->business();
        $owner = User::query()->where('email', 'owner@demo.test')->first();

        if ($business === null || $owner === null || ! $business->is_demo) {
            return;
        }

        $reference = 'ADJ-DEMO-'.strtoupper(substr(md5($business->id), 0, 6));

        Product::query()
            ->withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->whereDoesntHave('stockMovements')
            ->get()
            ->each(function (Product $product) use ($business, $owner, $reference): void {
                StockMovement::query()->create([
                    'business_id' => $business->id,
                    'product_id' => $product->id,
                    'movement_date' => now()->toDateString(),
                    'movement_type' => StockMovementType::Other,
                    'direction' => StockDirection::In,
                    'quantity' => 10,
                    'source_type' => 'product',
                    'source_id' => $product->id,
                    'reference' => $reference,
                    'notes' => 'Demo stock ledger entry',
                    'created_by' => $owner->id,
                ]);
            });
    }

    private function resolveDemoContext(): void
    {
        $owner = User::query()->where('email', 'owner@demo.test')->first();

        if ($owner === null) {
            return;
        }

        $business = $owner->businesses()->first();

        if ($business === null || ! $business->is_demo) {
            return;
        }

        app(BusinessContext::class)->setBusiness($business, $owner);
    }
}
