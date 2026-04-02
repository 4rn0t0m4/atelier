<?php

namespace Tests\Unit;

use App\Models\ProductAddon;
use App\Models\ProductAddonGroup;
use App\Services\AddonPriceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddonPriceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private AddonPriceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new AddonPriceCalculator();
    }

    public function test_empty_addons_returns_zero(): void
    {
        $result = $this->calculator->calculate([], 10.00, 1);

        $this->assertEquals(0.0, $result['flat']);
        $this->assertEquals(0.0, $result['per_unit']);
        $this->assertEquals(0.0, $result['total']);
    }

    public function test_flat_fee_added_once_regardless_of_quantity(): void
    {
        $addon = ProductAddon::factory()->flatFee(5.00)->create();

        $result = $this->calculator->calculate(
            [$addon->id => ['value' => 'Arnaud']],
            20.00,
            3
        );

        $this->assertEquals(5.00, $result['flat']);
        $this->assertEquals(0.0, $result['per_unit']);
        $this->assertEquals(5.00, $result['total']);
    }

    public function test_quantity_based_multiplied_by_quantity(): void
    {
        $addon = ProductAddon::factory()->quantityBased(2.00)->create();

        $result = $this->calculator->calculate(
            [$addon->id => ['value' => 'Test']],
            20.00,
            4
        );

        $this->assertEquals(0.0, $result['flat']);
        $this->assertEquals(2.00, $result['per_unit']);
        $this->assertEquals(8.00, $result['total']);
    }

    public function test_percentage_based_on_base_price(): void
    {
        $addon = ProductAddon::factory()->percentageBased(10)->create();

        $result = $this->calculator->calculate(
            [$addon->id => ['value' => 'Oui']],
            50.00,
            2
        );

        // 10% de 50 = 5.00 per unit, x2 = 10.00
        $this->assertEquals(0.0, $result['flat']);
        $this->assertEquals(5.00, $result['per_unit']);
        $this->assertEquals(10.00, $result['total']);
    }

    public function test_mixed_addon_types(): void
    {
        $group = ProductAddonGroup::factory()->create();
        $flatAddon = ProductAddon::factory()->flatFee(3.00)->create(['group_id' => $group->id]);
        $qtyAddon = ProductAddon::factory()->quantityBased(1.50)->create(['group_id' => $group->id]);

        $result = $this->calculator->calculate(
            [
                $flatAddon->id => ['value' => 'Gravure'],
                $qtyAddon->id => ['value' => 'Doré'],
            ],
            10.00,
            5
        );

        // flat: 3.00 (1x) + qty: 1.50 * 5 = 7.50 => total 10.50
        $this->assertEquals(3.00, $result['flat']);
        $this->assertEquals(1.50, $result['per_unit']);
        $this->assertEquals(10.50, $result['total']);
    }

    public function test_addon_with_options_by_index(): void
    {
        $addon = ProductAddon::factory()->withOptions([
            ['label' => 'Petit', 'price' => 2.00, 'price_type' => 'flat_fee'],
            ['label' => 'Grand', 'price' => 5.00, 'price_type' => 'quantity_based'],
        ])->create();

        // Sélection de l'option "Grand" (index 1, quantity_based)
        $result = $this->calculator->calculate(
            [$addon->id => ['value' => 'Grand', 'option_index' => 1]],
            20.00,
            3
        );

        $this->assertEquals(0.0, $result['flat']);
        $this->assertEquals(5.00, $result['per_unit']);
        $this->assertEquals(15.00, $result['total']);
    }

    public function test_addon_with_options_by_label(): void
    {
        $addon = ProductAddon::factory()->withOptions([
            ['label' => 'Rouge', 'price' => 1.50, 'price_type' => 'flat_fee'],
            ['label' => 'Bleu', 'price' => 2.50, 'price_type' => 'flat_fee'],
        ])->create();

        // Sélection par label sans option_index
        $result = $this->calculator->calculate(
            [$addon->id => ['value' => 'Bleu']],
            10.00,
            1
        );

        $this->assertEquals(2.50, $result['flat']);
        $this->assertEquals(0.0, $result['per_unit']);
        $this->assertEquals(2.50, $result['total']);
    }

    public function test_empty_value_is_ignored(): void
    {
        $addon = ProductAddon::factory()->flatFee(5.00)->create();

        $result = $this->calculator->calculate(
            [$addon->id => ['value' => '']],
            10.00,
            1
        );

        $this->assertEquals(0.0, $result['total']);
    }

    public function test_nonexistent_addon_id_is_ignored(): void
    {
        $result = $this->calculator->calculate(
            [99999 => ['value' => 'Test']],
            10.00,
            1
        );

        $this->assertEquals(0.0, $result['total']);
    }

    public function test_total_method_returns_float(): void
    {
        $addon = ProductAddon::factory()->flatFee(3.50)->create();

        $total = $this->calculator->total(
            [$addon->id => ['value' => 'Test']],
            10.00,
            1
        );

        $this->assertEquals(3.50, $total);
    }

    public function test_format_with_label_and_value(): void
    {
        $formatted = $this->calculator->format(['label' => 'Prénom', 'value' => 'Arnaud']);
        $this->assertEquals('Prénom : Arnaud', $formatted);
    }

    public function test_format_with_empty_value(): void
    {
        $formatted = $this->calculator->format(['label' => 'Gravure', 'value' => '']);
        $this->assertEquals('Gravure', $formatted);
    }

    public function test_percentage_rounding(): void
    {
        $addon = ProductAddon::factory()->percentageBased(15)->create();

        $result = $this->calculator->calculate(
            [$addon->id => ['value' => 'Oui']],
            33.33,
            1
        );

        // 15% de 33.33 = 5.00 (arrondi à 2 décimales)
        $this->assertEquals(5.00, $result['per_unit']);
        $this->assertEquals(5.00, $result['total']);
    }
}
