<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'category_id' => ProductCategory::factory(),
            'name' => $this->faker->words(3, true),
            'slug' => $this->faker->unique()->slug(2),
            'price' => $this->faker->randomFloat(2, 5, 100),
            'is_active' => true,
            'stock_status' => 'instock',
            'manage_stock' => false,
        ];
    }

    public function withSalePrice(float $salePrice): static
    {
        return $this->state(['sale_price' => $salePrice]);
    }

    public function withStock(int $quantity): static
    {
        return $this->state([
            'manage_stock' => true,
            'stock_quantity' => $quantity,
        ]);
    }
}
