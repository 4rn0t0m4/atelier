<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function addProductToCart(Product $product, int $qty = 1): void
    {
        $cart = app(CartService::class);
        $cart->add($product, $qty);
    }

    public function test_checkout_redirects_when_cart_empty(): void
    {
        $this->get('/commande')->assertRedirect(route('cart.index'));
    }

    public function test_checkout_page_loads_with_items(): void
    {
        $product = Product::factory()->create(['price' => 25.00]);
        $this->addProductToCart($product);

        $this->get('/commande')->assertStatus(200);
    }

    public function test_checkout_prefills_for_authenticated_user(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Marie',
            'last_name' => 'Dupont',
            'email' => 'marie@example.com',
            'address_1' => '12 rue des Lilas',
            'city' => 'Paris',
            'postcode' => '75001',
            'country' => 'FR',
        ]);

        $product = Product::factory()->create(['price' => 25.00]);

        $this->actingAs($user);
        $this->addProductToCart($product);

        $this->get('/commande')
            ->assertStatus(200)
            ->assertSee('Marie');
    }

    public function test_checkout_store_validation(): void
    {
        $product = Product::factory()->create(['price' => 25.00]);
        $this->addProductToCart($product);

        $this->post('/commande', [])
            ->assertSessionHasErrors([
                'billing_first_name',
                'billing_last_name',
                'billing_email',
                'billing_address_1',
                'billing_city',
                'billing_postcode',
                'billing_country',
                'shipping_method',
                'payment_method',
            ]);
    }

    public function test_checkout_validates_shipping_method_for_country(): void
    {
        $product = Product::factory()->create(['price' => 25.00]);
        $this->addProductToCart($product);

        $this->post('/commande', [
            'billing_first_name' => 'Jean',
            'billing_last_name' => 'Dupont',
            'billing_email' => 'jean@example.com',
            'billing_address_1' => '1 rue Test',
            'billing_city' => 'Paris',
            'billing_postcode' => '75001',
            'billing_country' => 'GB',
            'shipping_same' => true,
            'shipping_method' => 'boxtal', // boxtal n'est pas dispo pour GB
            'payment_method' => 'stripe',
        ])->assertSessionHasErrors(['shipping_method']);
    }

    public function test_checkout_redirects_when_stock_unavailable(): void
    {
        $product = Product::factory()->withStock(0)->create(['price' => 25.00]);
        $this->addProductToCart($product);

        $this->post('/commande', $this->validCheckoutData())
            ->assertRedirect(route('cart.index'));
    }

    public function test_daily_order_limit(): void
    {
        \App\Models\Setting::set('daily_order_limit', 1);

        // Créer une commande existante "processing" aujourd'hui
        \App\Models\Order::factory()->processing()->create();

        $product = Product::factory()->create(['price' => 25.00]);
        $this->addProductToCart($product);

        $this->get('/commande')
            ->assertRedirect(route('cart.index'));
    }

    private function validCheckoutData(): array
    {
        return [
            'billing_first_name' => 'Jean',
            'billing_last_name' => 'Dupont',
            'billing_email' => 'jean@example.com',
            'billing_phone' => '0612345678',
            'billing_address_1' => '1 rue de la Paix',
            'billing_city' => 'Paris',
            'billing_postcode' => '75001',
            'billing_country' => 'FR',
            'shipping_same' => true,
            'shipping_method' => 'colissimo',
            'payment_method' => 'stripe',
        ];
    }
}
