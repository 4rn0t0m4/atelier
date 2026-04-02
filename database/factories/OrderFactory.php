<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'number' => 'CMD-' . $this->faker->unique()->bothify('??###'),
            'status' => 'pending',
            'subtotal' => 50.00,
            'discount_total' => 0,
            'shipping_total' => 7.90,
            'tax_total' => 0,
            'total' => 57.90,
            'currency' => 'EUR',
            'payment_method' => 'stripe',
            'billing_first_name' => $this->faker->firstName(),
            'billing_last_name' => $this->faker->lastName(),
            'billing_email' => $this->faker->safeEmail(),
            'billing_phone' => $this->faker->phoneNumber(),
            'billing_address_1' => $this->faker->streetAddress(),
            'billing_city' => $this->faker->city(),
            'billing_postcode' => $this->faker->postcode(),
            'billing_country' => 'FR',
            'shipping_first_name' => $this->faker->firstName(),
            'shipping_last_name' => $this->faker->lastName(),
            'shipping_address_1' => $this->faker->streetAddress(),
            'shipping_city' => $this->faker->city(),
            'shipping_postcode' => $this->faker->postcode(),
            'shipping_country' => 'FR',
            'shipping_method' => 'Livraison à domicile (Colissimo)',
            'shipping_key' => 'colissimo',
        ];
    }

    public function processing(): static
    {
        return $this->state([
            'status' => 'processing',
            'paid_at' => now(),
        ]);
    }

    public function withStripe(string $intentId = 'pi_test_123'): static
    {
        return $this->state(['stripe_payment_intent_id' => $intentId]);
    }
}
