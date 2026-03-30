<?php

namespace App\Console\Commands\Migrate;

use App\Models\DiscountRule;

class WpCoupons extends WpImportCommand
{
    protected $signature = 'migrate:wp-coupons';
    protected $description = 'Importe les coupons depuis WooCommerce';

    private array $discountTypeMap = [
        'percent' => 'percentage',
        'fixed_cart' => 'fixed_cart',
        'fixed_product' => 'fixed_cart',
    ];

    public function handle(): int
    {
        $this->info('Import des coupons WooCommerce...');
        $this->safeTruncate('discount_rules');

        $categoryMap = $this->loadMap('wp_category_map.json');
        $productMap = $this->loadMap('wp_product_map.json');

        $coupons = $this->wp()
            ->table('posts')
            ->where('post_type', 'shop_coupon')
            ->orderBy('ID')
            ->get();

        $created = 0;

        foreach ($coupons as $c) {
            $meta = $this->postMeta($c->ID);

            $discountType = $this->discountTypeMap[$meta['discount_type'] ?? ''] ?? 'percentage';

            // Catégories cibles
            $targetCategories = null;
            $wcCats = $meta['product_categories'] ?? '';
            if ($wcCats) {
                $wcCatIds = @unserialize($wcCats);
                if (is_array($wcCatIds)) {
                    $targetCategories = array_values(array_filter(
                        array_map(fn ($id) => $categoryMap[(int) $id] ?? null, $wcCatIds)
                    ));
                }
            }

            // Produits cibles
            $targetProducts = null;
            $wcProds = $meta['product_ids'] ?? '';
            if ($wcProds) {
                $wpIds = is_string($wcProds) ? explode(',', $wcProds) : [];
                $targetProducts = array_values(array_filter(
                    array_map(fn ($id) => $productMap[(int) trim($id)] ?? null, $wpIds)
                ));
            }

            // Date d'expiration
            $expiresAt = ! empty($meta['date_expires'])
                ? \Carbon\Carbon::createFromTimestamp((int) $meta['date_expires'])
                : null;

            DiscountRule::create([
                'name' => strtoupper($c->post_title),
                'coupon_code' => strtolower($c->post_title),
                'is_active' => $c->post_status === 'publish',
                'type' => 'coupon',
                'discount_type' => $discountType,
                'discount_amount' => (float) ($meta['coupon_amount'] ?? 0),
                'target_categories' => $targetCategories ?: null,
                'target_products' => $targetProducts ?: null,
                'min_cart_value' => ! empty($meta['minimum_amount']) ? (float) $meta['minimum_amount'] : null,
                'max_cart_value' => ! empty($meta['maximum_amount']) ? (float) $meta['maximum_amount'] : null,
                'min_quantity' => null,
                'max_quantity' => null,
                'starts_at' => null,
                'ends_at' => $expiresAt,
                'stackable' => ($meta['individual_use'] ?? 'no') !== 'yes',
                'sort_order' => 0,
                'usage_limit' => ((int) ($meta['usage_limit'] ?? 0)) ?: null,
                'usage_count' => (int) ($meta['usage_count'] ?? 0),
                'free_shipping' => ($meta['free_shipping'] ?? 'no') === 'yes',
                'exclude_sale_items' => ($meta['exclude_sale_items'] ?? 'no') === 'yes',
            ]);

            $created++;
        }

        $this->printResult('Coupons', $created);

        return self::SUCCESS;
    }
}
