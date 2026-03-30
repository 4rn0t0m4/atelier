<?php

namespace App\Console\Commands\Migrate;

use App\Models\Product;

class WpProducts extends WpImportCommand
{
    protected $signature = 'migrate:wp-products';
    protected $description = 'Importe les produits depuis WooCommerce';

    public function handle(): int
    {
        $this->info('Import des produits WooCommerce...');
        $this->safeTruncate('products');

        $categoryMap = $this->loadMap('wp_category_map.json');
        $mediaMap = $this->loadMap('wp_media_map.json');

        $products = $this->wp()
            ->table('posts')
            ->where('post_type', 'product')
            ->whereIn('post_status', ['publish', 'draft', 'private'])
            ->orderBy('ID')
            ->get();

        $map = [];
        $created = 0;
        $usedSlugs = [];

        foreach ($products as $wp) {
            $meta = $this->postMeta($wp->ID);

            // Catégorie principale
            $wpCatId = $this->wp()
                ->table('term_relationships as tr')
                ->join('term_taxonomy as tt', 'tr.term_taxonomy_id', '=', 'tt.term_taxonomy_id')
                ->where('tr.object_id', $wp->ID)
                ->where('tt.taxonomy', 'product_cat')
                ->value('tt.term_id');

            // Slug unique
            $slug = $wp->post_name;
            if (isset($usedSlugs[$slug])) {
                $slug .= '-' . (++$usedSlugs[$slug]);
            } else {
                $usedSlugs[$slug] = 1;
            }

            // Featured image
            $thumbnailId = $meta['_thumbnail_id'] ?? null;
            $featuredImageId = $thumbnailId ? ($mediaMap[(int) $thumbnailId] ?? null) : null;

            // Gallery
            $galleryStr = $meta['_product_image_gallery'] ?? '';
            $galleryIds = [];
            if ($galleryStr) {
                foreach (explode(',', $galleryStr) as $wpAttId) {
                    $laravelId = $mediaMap[(int) trim($wpAttId)] ?? null;
                    if ($laravelId) {
                        $galleryIds[] = $laravelId;
                    }
                }
            }

            // Total sales
            $totalSales = (int) ($meta['total_sales'] ?? 0);

            // Yoast SEO
            $yoastTitle = $meta['_yoast_wpseo_title'] ?? null;
            $yoastDesc = $meta['_yoast_wpseo_metadesc'] ?? null;

            $product = Product::create([
                'category_id' => $wpCatId ? ($categoryMap[(int) $wpCatId] ?? null) : null,
                'name' => $wp->post_title,
                'slug' => $slug,
                'short_description' => $wp->post_excerpt ?: null,
                'description' => $wp->post_content ?: null,
                'price' => (float) ($meta['_regular_price'] ?? $meta['_price'] ?? 0),
                'sale_price' => ! empty($meta['_sale_price']) ? (float) $meta['_sale_price'] : null,
                'sku' => $meta['_sku'] ?? null,
                'stock_quantity' => isset($meta['_stock']) ? (int) $meta['_stock'] : null,
                'manage_stock' => ($meta['_manage_stock'] ?? 'no') === 'yes',
                'stock_status' => $meta['_stock_status'] ?? 'instock',
                'weight' => ! empty($meta['_weight']) ? (float) $meta['_weight'] : null,
                'is_active' => $wp->post_status === 'publish',
                'is_featured' => ($meta['_featured'] ?? 'no') === 'yes',
                'meta_title' => $yoastTitle ?: null,
                'meta_description' => $yoastDesc ?: null,
                'featured_image_id' => $featuredImageId,
                'gallery_image_ids' => $galleryIds ?: null,
                'total_sales' => $totalSales,
            ]);

            $map[$wp->ID] = $product->id;
            $created++;
        }

        $this->saveMap('wp_product_map.json', $map);
        $this->printResult('Produits', $created);

        return self::SUCCESS;
    }
}
