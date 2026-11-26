<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderReshipment;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderReshipmentFactory extends Factory
{
    protected $model = OrderReshipment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory()->processing(),
            'token' => OrderReshipment::generateToken(),
            'amount' => 5.00,
            'status' => OrderReshipment::STATUS_PENDING,
        ];
    }

    public function withRelay(): static
    {
        return $this->state([
            'relay_point_code' => 'FR12345',
            'relay_network' => 'CHRP_NETWORK',
            'relay_point_name' => 'Tabac du Centre',
            'relay_point_address' => '12 rue de la Gare, 14000 Caen',
            'payment_method' => 'stripe',
            'stripe_payment_intent_id' => 'pi_reship_123',
        ]);
    }

    public function paid(): static
    {
        return $this->withRelay()->state([
            'status' => OrderReshipment::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => OrderReshipment::STATUS_CANCELLED]);
    }
}
