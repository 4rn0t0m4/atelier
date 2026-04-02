<?php

namespace Database\Factories;

use App\Models\ProductAddon;
use App\Models\ProductAddonGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductAddonFactory extends Factory
{
    protected $model = ProductAddon::class;

    public function definition(): array
    {
        return [
            'group_id' => ProductAddonGroup::factory(),
            'label' => $this->faker->words(2, true),
            'type' => 'text',
            'price' => 0,
            'price_type' => 'flat_fee',
            'adjust_price' => false,
            'required' => false,
            'sync_qty' => false,
            'sort_order' => 0,
        ];
    }

    public function flatFee(float $price): static
    {
        return $this->state([
            'price' => $price,
            'price_type' => 'flat_fee',
            'adjust_price' => true,
        ]);
    }

    public function quantityBased(float $price): static
    {
        return $this->state([
            'price' => $price,
            'price_type' => 'quantity_based',
            'adjust_price' => true,
        ]);
    }

    public function percentageBased(float $percentage): static
    {
        return $this->state([
            'price' => $percentage,
            'price_type' => 'percentage_based',
            'adjust_price' => true,
        ]);
    }

    public function withOptions(array $options): static
    {
        return $this->state([
            'type' => 'select',
            'options' => $options,
        ]);
    }
}
