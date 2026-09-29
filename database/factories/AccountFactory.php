<?php

namespace Database\Factories;

use App\Domain\Accounting\Enums\AccountStatus;
use App\Domain\Accounting\Enums\AccountType;
use App\Models\Account;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(AccountType::cases());

        return [
            'business_id' => Business::factory(),
            'parent_id' => null,
            'name' => Str::headline(fake()->unique()->words(2, true)),
            'code' => (string) fake()->unique()->numberBetween(1000, 9999),
            'type' => $type,
            'group' => $type->groupLabel(),
            'status' => AccountStatus::Active,
            'is_system' => false,
            'description' => null,
            'opening_balance' => 0,
            'opening_balance_side' => null,
        ];
    }

    public function groupHeader(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AccountStatus::Inactive,
        ]);
    }

    public function ofType(AccountType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
            'group' => $type->groupLabel(),
        ]);
    }

    public function forBusiness(Business $business): static
    {
        return $this->state(fn (array $attributes) => [
            'business_id' => $business->id,
        ]);
    }
}
