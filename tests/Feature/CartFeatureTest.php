<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductAddon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_page_loads(): void
    {
        $this->get('/panier')->assertStatus(200);
    }

    public function test_add_product_to_cart(): void
    {
        $product = Product::factory()->create(['price' => 15.00]);

        $response = $this->post('/panier/ajouter', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Vérifier le contenu de la session
        $this->get('/panier')->assertSee($product->name);
    }

    public function test_add_product_with_addons(): void
    {
        $product = Product::factory()->create(['price' => 10.00]);
        $addon = ProductAddon::factory()->flatFee(3.00)->create();

        $this->post('/panier/ajouter', [
            'product_id' => $product->id,
            'quantity' => 1,
            'addons' => [
                $addon->id => ['value' => 'Arnaud'],
            ],
        ]);

        $this->get('/panier')->assertSee($product->name);
    }

    public function test_add_invalid_product_fails(): void
    {
        $this->post('/panier/ajouter', [
            'product_id' => 99999,
            'quantity' => 1,
        ])->assertSessionHasErrors(['product_id']);
    }

    public function test_update_cart_quantity(): void
    {
        $product = Product::factory()->create(['price' => 10.00]);

        // Ajouter d'abord
        $this->post('/panier/ajouter', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        // Récupérer la clé du panier
        $cart = session('cart', []);
        $key = array_key_first($cart);

        $this->patch("/panier/{$key}", ['quantity' => 5])
            ->assertRedirect(route('cart.index'));
    }

    public function test_remove_from_cart(): void
    {
        $product = Product::factory()->create(['price' => 10.00]);

        $this->post('/panier/ajouter', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $cart = session('cart', []);
        $key = array_key_first($cart);

        $this->delete("/panier/{$key}")
            ->assertRedirect(route('cart.index'));

        // Panier devrait être vide
        $this->assertEquals(0, count(session('cart', [])));
    }

    public function test_mini_cart_returns_html(): void
    {
        $this->get('/panier/mini')->assertStatus(200);
    }

    public function test_quantity_validation(): void
    {
        $product = Product::factory()->create();

        $this->post('/panier/ajouter', [
            'product_id' => $product->id,
            'quantity' => 0,
        ])->assertSessionHasErrors(['quantity']);

        $this->post('/panier/ajouter', [
            'product_id' => $product->id,
            'quantity' => 100,
        ])->assertSessionHasErrors(['quantity']);
    }
}
