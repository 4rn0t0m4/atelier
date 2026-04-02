<?php

namespace Database\Factories;

use App\Models\ProductAddonGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductAddonGroupFactory extends Factory
{
    protected $model = ProductAddonGroup::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'is_global' => false,
            'sort_order' => 0,
        ];
    }
}
