<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => fake()->unique()->word(),
            'symbol' => strtoupper(fake()->unique()->lexify('???')),
            'decimal_places' => 0,
            'is_default' => false,
            'is_system' => false,
        ];
    }

    public function forBusiness(Business $business): static
    {
        return $this->state(fn (array $attributes) => [
            'business_id' => $business->id,
        ]);
    }
}
