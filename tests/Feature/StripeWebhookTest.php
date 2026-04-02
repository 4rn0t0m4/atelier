<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\OrderFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    // --- OrderFulfillmentService (testé directement car le webhook dépend de Stripe SDK) ---

    public function test_confirm_payment_changes_status(): void
    {
        $order = Order::factory()->withStripe()->create(['status' => 'pending']);

        $service = app(OrderFulfillmentService::class);
        $result = $service->confirmPayment($order);

        $this->assertTrue($result);
        $this->assertEquals('processing', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->paid_at);
    }

    public function test_confirm_payment_is_idempotent(): void
    {
        $order = Order::factory()->withStripe()->processing()->create();

        $service = app(OrderFulfillmentService::class);
        $result = $service->confirmPayment($order);

        $this->assertFalse($result);
    }

    public function test_confirm_payment_decrements_stock(): void
    {
        $product = Product::factory()->withStock(10)->create(['price' => 25.00]);
        $order = Order::factory()->withStripe()->create(['status' => 'pending']);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $service = app(OrderFulfillmentService::class);
        $service->confirmPayment($order);

        $this->assertEquals(7, $product->fresh()->stock_quantity);
        $this->assertEquals(3, $product->fresh()->total_sales);
    }

    public function test_stock_set_to_outofstock_when_zero(): void
    {
        $product = Product::factory()->withStock(2)->create(['price' => 25.00]);
        $order = Order::factory()->withStripe()->create(['status' => 'pending']);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $service = app(OrderFulfillmentService::class);
        $service->confirmPayment($order);

        $this->assertEquals(0, $product->fresh()->stock_quantity);
        $this->assertEquals('outofstock', $product->fresh()->stock_status);
    }

    public function test_non_managed_stock_not_decremented(): void
    {
        $product = Product::factory()->create([
            'price' => 25.00,
            'manage_stock' => false,
            'stock_quantity' => null,
        ]);
        $order = Order::factory()->withStripe()->create(['status' => 'pending']);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $service = app(OrderFulfillmentService::class);
        $service->confirmPayment($order);

        // Pas d'erreur, le produit non-managé n'est pas touché
        $this->assertEquals('processing', $order->fresh()->status);
    }

    // --- Webhook endpoint (test structure) ---

    public function test_webhook_rejects_invalid_signature(): void
    {
        $this->post('/stripe/webhook', [], [
            'Stripe-Signature' => 'invalid',
            'Content-Type' => 'application/json',
        ])->assertStatus(400);
    }

    // --- OrderService (validateCartStock) ---

    public function test_validate_cart_removes_inactive_product(): void
    {
        $product = Product::factory()->create(['is_active' => false, 'price' => 25.00]);
        $cart = app(\App\Services\CartService::class);
        $cart->add($product, 1);

        // Désactiver après l'ajout
        $product->update(['is_active' => false]);

        $items = $cart->itemsWithProducts();
        $orderService = app(\App\Services\OrderService::class);
        $result = $orderService->validateCartStock($items);

        $this->assertFalse($result['ok']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_validate_cart_reduces_quantity_when_stock_low(): void
    {
        $product = Product::factory()->withStock(2)->create(['price' => 25.00]);
        $cart = app(\App\Services\CartService::class);
        $cart->add($product, 5);

        $items = $cart->itemsWithProducts();
        $orderService = app(\App\Services\OrderService::class);
        $result = $orderService->validateCartStock($items);

        $this->assertFalse($result['ok']);
        // La quantité a été réduite à 2
        $cartItems = $cart->all();
        $item = reset($cartItems);
        $this->assertEquals(2, $item['quantity']);
    }

    public function test_validate_cart_updates_price_when_changed(): void
    {
        $product = Product::factory()->create(['price' => 25.00]);
        $cart = app(\App\Services\CartService::class);
        $cart->add($product, 1);

        // Changer le prix
        $product->update(['price' => 30.00]);

        $items = $cart->itemsWithProducts();
        $orderService = app(\App\Services\OrderService::class);
        $result = $orderService->validateCartStock($items);

        $this->assertTrue($result['cartUpdated']);
    }
}
