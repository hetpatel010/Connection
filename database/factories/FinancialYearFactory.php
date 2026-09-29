<?php

namespace Database\Factories;

use App\Domain\FinancialYear\Enums\FinancialYearStatus;
use App\Models\Business;
use App\Models\FinancialYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialYear>
 */
class FinancialYearFactory extends Factory
{
    protected $model = FinancialYear::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'label' => '2025-26',
            'starts_on' => '2025-04-01',
            'ends_on' => '2026-03-31',
            'status' => FinancialYearStatus::Open,
            'is_current' => true,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => FinancialYearStatus::Closed,
            'closed_at' => now(),
        ]);
    }
}
