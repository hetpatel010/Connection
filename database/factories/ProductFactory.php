<?php

namespace Database\Factories;

use App\Domain\Products\GstRate;
use App\Domain\Shared\Enums\RecordStatus;
use App\Models\Business;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'unit_id' => Unit::factory(),
            'name' => fake()->words(3, true),
            'sku' => fake()->unique()->bothify('SKU-####'),
            'selling_price' => fake()->numberBetween(10000, 500000),
            'purchase_price' => fake()->numberBetween(5000, 400000),
            'tax_rate' => fake()->randomElement(GstRate::slabs()),
            'tax_inclusive' => false,
            'status' => RecordStatus::Active,
        ];
    }

    public function forBusiness(Business $business): static
    {
        return $this->state(function (array $attributes) use ($business): array {
            $unitId = Unit::query()
                ->withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->value('id');

            if ($unitId === null) {
                $unitId = Unit::factory()->forBusiness($business)->create()->id;
            }

            return [
                'business_id' => $business->id,
                'unit_id' => $unitId,
            ];
        });
    }
}
