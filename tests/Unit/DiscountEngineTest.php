<?php

namespace Tests\Unit;

use App\Models\DiscountRule;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\DiscountEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountEngineTest extends TestCase
{
    use RefreshDatabase;

    private DiscountEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new DiscountEngine();
    }

    private function makeCartItems(array $items): array
    {
        return array_map(fn ($item) => array_merge([
            'quantity' => 1,
            'price' => 10.00,
            'unit_price' => 10.00,
            'addon_price_per_unit' => 0,
            'addon_price_flat' => 0,
            'product' => null,
        ], $item), $items);
    }

    // --- Pourcentage ---

    public function test_percentage_discount(): void
    {
        DiscountRule::factory()->percentage(20)->create();

        $result = $this->engine->calculate(
            $this->makeCartItems([['price' => 50.00, 'unit_price' => 50.00]]),
            50.00
        );

        $this->assertEquals(10.00, $result['amount']);
        $this->assertFalse($result['free_shipping']);
    }

    // --- Montant fixe ---

    public function test_fixed_amount_discount(): void
    {
        DiscountRule::factory()->fixedAmount(7.50)->create();

        $result = $this->engine->calculate(
            $this->makeCartItems([['price' => 30.00, 'unit_price' => 30.00]]),
            30.00
        );

        $this->assertEquals(7.50, $result['amount']);
    }

    public function test_fixed_discount_capped_at_subtotal(): void
    {
        DiscountRule::factory()->fixedAmount(100.00)->create();

        $result = $this->engine->calculate(
            $this->makeCartItems([['price' => 20.00, 'unit_price' => 20.00]]),
            20.00
        );

        $this->assertEquals(20.00, $result['amount']);
    }

    // --- Codes promo ---

    public function test_coupon_required_and_provided(): void
    {
        DiscountRule::factory()->coupon('PROMO10')->percentage(10)->create();

        $result = $this->engine->calculate(
            $this->makeCartItems([['price' => 100.00, 'unit_price' => 100.00]]),
            100.00,
            'PROMO10'
        );

        $this->assertEquals(10.00, $result['amount']);
    }

    public function test_coupon_required_but_not_provided(): void
    {
        DiscountRule::factory()->coupon('PROMO10')->percentage(10)->create();

        $result = $this->engine->calculate(
            $this->makeCartItems([['price' => 100.00, 'unit_price' => 100.00]]),
            100.00
        );

        $this->assertEquals(0.0, $result['amount']);
    }

    public function test_coupon_case_insensitive(): void
    {
        DiscountRule::factory()->coupon('PROMO10')->percentage(10)->create();

        $result = $this->engine->calculate(
            $this->makeCartItems([['price' => 100.00, 'unit_price' => 100.00]]),
            100.00,
            'promo10'
        );

        $this->assertEquals(10.00, $result['amount']);
    }

    // --- Conditions ---

    public function test_min_cart_value(): void
    {
        DiscountRule::factory()->percentage(10)->create(['min_cart_value' => 50.00]);

        $belowMin = $this->engine->calculate(
            $this->makeCartItems([['price' => 30.00, 'unit_price' => 30.00]]),
            30.00
        );
        $this->assertEquals(0.0, $belowMin['amount']);

        $aboveMin = $this->engine->calculate(
            $this->makeCartItems([['price' => 60.00, 'unit_price' => 60.00]]),
            60.00
        );
        $this->assertEquals(6.00, $aboveMin['amount']);
    }

    public function test_max_cart_value(): void
    {
        DiscountRule::factory()->percentage(10)->create(['max_cart_value' => 50.00]);

        $result = $this->engine->calculate(
            $this->makeCartItems([['price' => 80.00, 'unit_price' => 80.00]]),
            80.00
        );

        $this->assertEquals(0.0, $result['amount']);
    }

    // --- Inactive ---

    public function test_inactive_rule_is_ignored(): void
    {
        DiscountRule::factory()->inactive()->percentage(50)->create();

        $result = $this->engine->calculate(
            $this->makeCartItems([['price' => 100.00, 'unit_price' => 100.00]]),
            100.00
        );

        $this->assertEquals(0.0, $result['amount']);
    }

    // --- Free shipping ---

    public function test_free_shipping_flag(): void
    {
        DiscountRule::factory()->freeShipping()->fixedAmount(0)->create();

        $result = $this->engine->calculate(
            $this->makeCartItems([['price' => 50.00, 'unit_price' => 50.00]]),
            50.00
        );

        $this->assertTrue($result['free_shipping']);
    }

    // --- Catégories ciblées ---

    public function test_target_categories(): void
    {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 40.00,
        ]);

        DiscountRule::factory()->percentage(25)->create([
            'target_categories' => [$category->id],
        ]);

        $result = $this->engine->calculate(
            $this->makeCartItems([
                ['price' => 40.00, 'unit_price' => 40.00, 'quantity' => 1, 'product' => $product],
            ]),
            40.00
        );

        $this->assertEquals(10.00, $result['amount']);
    }

    public function test_target_categories_no_match(): void
    {
        $category = ProductCategory::factory()->create();
        $otherCategory = ProductCategory::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $otherCategory->id,
            'price' => 40.00,
        ]);

        DiscountRule::factory()->percentage(25)->create([
            'target_categories' => [$category->id],
        ]);

        $result = $this->engine->calculate(
            $this->makeCartItems([
                ['price' => 40.00, 'unit_price' => 40.00, 'product' => $product],
            ]),
            40.00
        );

        $this->assertEquals(0.0, $result['amount']);
    }

    // --- Produits ciblés ---

    public function test_target_products(): void
    {
        $product = Product::factory()->create(['price' => 30.00]);

        DiscountRule::factory()->fixedAmount(5.00)->create([
            'target_products' => [$product->id],
        ]);

        $result = $this->engine->calculate(
            $this->makeCartItems([
                ['price' => 30.00, 'unit_price' => 30.00, 'product' => $product],
            ]),
            30.00
        );

        $this->assertEquals(5.00, $result['amount']);
    }

    // --- Stackable ---

    public function test_non_stackable_stops_after_first(): void
    {
        DiscountRule::factory()->percentage(10)->create(['sort_order' => 1, 'stackable' => false]);
        DiscountRule::factory()->percentage(20)->create(['sort_order' => 2, 'stackable' => false]);

        $result = $this->engine->calculate(
            $this->makeCartItems([['price' => 100.00, 'unit_price' => 100.00]]),
            100.00
        );

        $this->assertEquals(10.00, $result['amount']);
        $this->assertCount(1, $result['rules']);
    }

    public function test_stackable_rules_accumulate(): void
    {
        DiscountRule::factory()->fixedAmount(5.00)->create(['sort_order' => 1, 'stackable' => true]);
        DiscountRule::factory()->fixedAmount(3.00)->create(['sort_order' => 2, 'stackable' => true]);

        $result = $this->engine->calculate(
            $this->makeCartItems([['price' => 100.00, 'unit_price' => 100.00]]),
            100.00
        );

        $this->assertEquals(8.00, $result['amount']);
        $this->assertCount(2, $result['rules']);
    }

    // --- Exclude sale items ---

    public function test_exclude_sale_items(): void
    {
        $product = Product::factory()->create([
            'price' => 30.00,
            'sale_price' => 20.00,
        ]);

        DiscountRule::factory()->percentage(10)->create(['exclude_sale_items' => true]);

        $result = $this->engine->calculate(
            $this->makeCartItems([
                ['price' => 20.00, 'unit_price' => 20.00, 'product' => $product],
            ]),
            20.00
        );

        // Tous les items sont en promo → la règle ne s'applique pas
        $this->assertEquals(0.0, $result['amount']);
    }

    // --- Validate coupon ---

    public function test_validate_coupon_valid(): void
    {
        DiscountRule::factory()->coupon('HIVER25')->create();

        $rule = $this->engine->validateCoupon('hiver25');

        $this->assertNotNull($rule);
        $this->assertEquals('HIVER25', $rule->coupon_code);
    }

    public function test_validate_coupon_invalid(): void
    {
        $rule = $this->engine->validateCoupon('NEXISTEPAS');

        $this->assertNull($rule);
    }

    public function test_validate_coupon_expired(): void
    {
        DiscountRule::factory()->coupon('EXPIRE')->create([
            'ends_at' => now()->subDay(),
        ]);

        $rule = $this->engine->validateCoupon('EXPIRE');

        $this->assertNull($rule);
    }

    public function test_validate_coupon_usage_limit_reached(): void
    {
        DiscountRule::factory()->coupon('LIMITE')->create([
            'usage_limit' => 5,
            'usage_count' => 5,
        ]);

        $rule = $this->engine->validateCoupon('LIMITE');

        $this->assertNull($rule);
    }

    // --- Quantité min/max ---

    public function test_min_quantity(): void
    {
        DiscountRule::factory()->percentage(10)->create(['min_quantity' => 3]);

        $below = $this->engine->calculate(
            $this->makeCartItems([['price' => 10.00, 'unit_price' => 10.00, 'quantity' => 2]]),
            20.00
        );
        $this->assertEquals(0.0, $below['amount']);

        $above = $this->engine->calculate(
            $this->makeCartItems([['price' => 10.00, 'unit_price' => 10.00, 'quantity' => 4]]),
            40.00
        );
        $this->assertEquals(4.00, $above['amount']);
    }

    // --- Label ---

    public function test_result_label(): void
    {
        DiscountRule::factory()->percentage(10)->create(['name' => 'Soldes été']);

        $result = $this->engine->calculate(
            $this->makeCartItems([['price' => 100.00, 'unit_price' => 100.00]]),
            100.00
        );

        $this->assertEquals('Soldes été', $result['label']);
    }

    public function test_no_discount_label_is_null(): void
    {
        $result = $this->engine->calculate(
            $this->makeCartItems([['price' => 100.00, 'unit_price' => 100.00]]),
            100.00
        );

        $this->assertNull($result['label']);
    }
}
