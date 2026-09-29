<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'legal_name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'state' => 'Gujarat',
            'country' => 'IN',
            'currency_code' => 'INR',
            'timezone' => 'Asia/Kolkata',
            'is_demo' => false,
        ];
    }

    public function demo(): static
    {
        return $this->state(fn () => ['is_demo' => true]);
    }
}
