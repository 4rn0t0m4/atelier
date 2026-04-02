<?php

namespace Database\Factories;

use App\Models\DiscountRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountRuleFactory extends Factory
{
    protected $model = DiscountRule::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'is_active' => true,
            'type' => 'cart',
            'discount_type' => 'percentage',
            'discount_amount' => 10,
            'stackable' => false,
            'free_shipping' => false,
            'sort_order' => 0,
            'usage_count' => 0,
        ];
    }

    public function coupon(string $code): static
    {
        return $this->state(['coupon_code' => $code]);
    }

    public function fixedAmount(float $amount): static
    {
        return $this->state([
            'discount_type' => 'fixed',
            'discount_amount' => $amount,
        ]);
    }

    public function percentage(float $percent): static
    {
        return $this->state([
            'discount_type' => 'percentage',
            'discount_amount' => $percent,
        ]);
    }

    public function freeShipping(): static
    {
        return $this->state(['free_shipping' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
