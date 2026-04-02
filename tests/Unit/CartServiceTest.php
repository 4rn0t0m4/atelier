<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductAddonGroup;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    private CartService $cart;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cart = app(CartService::class);
    }

    public function test_cart_starts_empty(): void
    {
        $this->assertEmpty($this->cart->all());
        $this->assertEquals(0, $this->cart->count());
        $this->assertEquals(0.0, $this->cart->subtotal());
    }

    public function test_add_simple_product(): void
    {
        $product = Product::factory()->create(['price' => 15.00]);

        $key = $this->cart->add($product, 2);

        $this->assertNotEmpty($key);
        $this->assertCount(1, $this->cart->all());
        $this->assertEquals(2, $this->cart->count());
        $this->assertEquals(30.00, $this->cart->subtotal());
    }

    public function test_add_same_product_increments_quantity(): void
    {
        $product = Product::factory()->create(['price' => 10.00]);

        $this->cart->add($product, 1);
        $this->cart->add($product, 2);

        $this->assertCount(1, $this->cart->all());
        $this->assertEquals(3, $this->cart->count());
        $this->assertEquals(30.00, $this->cart->subtotal());
    }

    public function test_different_addons_create_separate_lines(): void
    {
        $product = Product::factory()->create(['price' => 10.00]);
        $addon = ProductAddon::factory()->flatFee(2.00)->create();

        $key1 = $this->cart->add($product, 1, []);
        $key2 = $this->cart->add($product, 1, [
            $addon->id => ['value' => 'Arnaud'],
        ]);

        $this->assertNotEquals($key1, $key2);
        $this->assertCount(2, $this->cart->all());
    }

    public function test_same_product_same_addons_increments(): void
    {
        $product = Product::factory()->create(['price' => 10.00]);
        $addon = ProductAddon::factory()->flatFee(2.00)->create();
        $addons = [$addon->id => ['value' => 'Test']];

        $key1 = $this->cart->add($product, 1, $addons);
        $key2 = $this->cart->add($product, 1, $addons);

        $this->assertEquals($key1, $key2);
        $this->assertCount(1, $this->cart->all());
        $this->assertEquals(2, $this->cart->count());
    }

    public function test_update_quantity(): void
    {
        $product = Product::factory()->create(['price' => 10.00]);
        $key = $this->cart->add($product, 1);

        $this->cart->update($key, 5);

        $this->assertEquals(5, $this->cart->count());
        $this->assertEquals(50.00, $this->cart->subtotal());
    }

    public function test_update_to_zero_removes_item(): void
    {
        $product = Product::factory()->create(['price' => 10.00]);
        $key = $this->cart->add($product, 1);

        $this->cart->update($key, 0);

        $this->assertEmpty($this->cart->all());
    }

    public function test_remove_item(): void
    {
        $product = Product::factory()->create(['price' => 10.00]);
        $key = $this->cart->add($product, 1);

        $this->cart->remove($key);

        $this->assertEmpty($this->cart->all());
        $this->assertEquals(0, $this->cart->count());
    }

    public function test_clear_cart(): void
    {
        $product1 = Product::factory()->create(['price' => 10.00]);
        $product2 = Product::factory()->create(['price' => 20.00]);

        $this->cart->add($product1, 1);
        $this->cart->add($product2, 2);

        $this->cart->clear();

        $this->assertEmpty($this->cart->all());
        $this->assertEquals(0, $this->cart->count());
    }

    public function test_subtotal_with_flat_fee_addon(): void
    {
        $product = Product::factory()->create(['price' => 10.00]);
        $addon = ProductAddon::factory()->flatFee(5.00)->create();

        $this->cart->add($product, 3, [
            $addon->id => ['value' => 'Prénom'],
        ]);

        // (10.00 + 0 per_unit) * 3 + 5.00 flat = 35.00
        $this->assertEquals(35.00, $this->cart->subtotal());
    }

    public function test_subtotal_with_quantity_based_addon(): void
    {
        $product = Product::factory()->create(['price' => 10.00]);
        $addon = ProductAddon::factory()->quantityBased(2.00)->create();

        $this->cart->add($product, 3, [
            $addon->id => ['value' => 'Gravure'],
        ]);

        // (10.00 + 2.00 per_unit) * 3 + 0 flat = 36.00
        $this->assertEquals(36.00, $this->cart->subtotal());
    }

    public function test_line_total(): void
    {
        $item = [
            'price' => 10.00,
            'addon_price_per_unit' => 2.00,
            'addon_price_flat' => 3.00,
            'quantity' => 4,
        ];

        // (10 + 2) * 4 + 3 = 51
        $this->assertEquals(51.00, $this->cart->lineTotal($item));
    }

    public function test_update_price(): void
    {
        $product = Product::factory()->create(['price' => 10.00]);
        $key = $this->cart->add($product, 2);

        $this->cart->updatePrice($key, 15.00);

        $items = $this->cart->all();
        $this->assertEquals(15.00, $items[$key]['price']);
        $this->assertEquals(30.00, $this->cart->subtotal());
    }

    public function test_items_with_products(): void
    {
        $product = Product::factory()->create(['price' => 25.00]);
        $this->cart->add($product, 1);

        $items = $this->cart->itemsWithProducts();

        $item = reset($items);
        $this->assertNotNull($item['product']);
        $this->assertEquals($product->id, $item['product']->id);
        $this->assertEquals(25.00, $item['unit_price']);
    }

    public function test_addon_with_empty_value_is_ignored(): void
    {
        $product = Product::factory()->create(['price' => 10.00]);
        $addon = ProductAddon::factory()->flatFee(5.00)->create();

        $this->cart->add($product, 1, [
            $addon->id => ['value' => ''],
        ]);

        $items = $this->cart->all();
        $item = reset($items);

        // L'addon avec valeur vide ne devrait pas être enregistré
        $this->assertEmpty($item['addons']);
        $this->assertEquals(10.00, $this->cart->subtotal());
    }

    public function test_cart_stores_product_info(): void
    {
        $product = Product::factory()->create([
            'name' => 'Prénom en bois',
            'slug' => 'prenom-en-bois',
            'price' => 12.50,
        ]);

        $key = $this->cart->add($product, 1);
        $items = $this->cart->all();

        $this->assertEquals('Prénom en bois', $items[$key]['name']);
        $this->assertEquals('prenom-en-bois', $items[$key]['slug']);
        $this->assertEquals(12.50, $items[$key]['price']);
        $this->assertEquals($product->id, $items[$key]['product_id']);
    }

    public function test_sale_price_is_used(): void
    {
        $product = Product::factory()->create([
            'price' => 20.00,
            'sale_price' => 15.00,
        ]);

        $this->cart->add($product, 2);

        $this->assertEquals(30.00, $this->cart->subtotal());
    }
}
