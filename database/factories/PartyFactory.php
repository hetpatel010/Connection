<?php

namespace Database\Factories;

use App\Domain\Parties\Enums\PartyType;
use App\Domain\Shared\Enums\RecordStatus;
use App\Models\Business;
use App\Models\Party;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Party>
 */
class PartyFactory extends Factory
{
    protected $model = Party::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'party_type' => PartyType::Customer,
            'name' => fake()->company(),
            'legal_name' => fake()->optional()->company(),
            'phone' => fake()->optional()->numerify('##########'),
            'email' => fake()->optional()->safeEmail(),
            'billing_state' => fake()->state(),
            'billing_country' => 'IN',
            'status' => RecordStatus::Active,
        ];
    }

    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'party_type' => PartyType::Customer,
        ]);
    }

    public function supplier(): static
    {
        return $this->state(fn (array $attributes) => [
            'party_type' => PartyType::Supplier,
        ]);
    }

    public function forBusiness(Business $business): static
    {
        return $this->state(fn (array $attributes) => [
            'business_id' => $business->id,
            'billing_state' => $business->state,
        ]);
    }
}
