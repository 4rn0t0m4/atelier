<?php

namespace App\Console\Commands\Migrate;

use App\Models\ProductCategory;

class WpCategories extends WpImportCommand
{
    protected $signature = 'migrate:wp-categories';
    protected $description = 'Importe les catégories produit depuis WordPress';

    public function handle(): int
    {
        $this->info('Import des catégories produit...');
        $this->safeTruncate('product_categories');

        $categories = $this->wp()
            ->table('terms as t')
            ->join('term_taxonomy as tt', 't.term_id', '=', 'tt.term_id')
            ->where('tt.taxonomy', 'product_cat')
            ->orderBy('tt.parent')
            ->orderBy('t.name')
            ->get();

        $this->info("  {$categories->count()} catégories trouvées dans WordPress");

        $mediaMap = $this->loadMap('wp_media_map.json');
        $map = [];
        $created = 0;
        $errors = 0;

        // Pass 1 : créer toutes les catégories sans parent
        foreach ($categories as $cat) {
            try {
                $thumbnailId = $this->wp()
                    ->table('termmeta')
                    ->where('term_id', $cat->term_id)
                    ->where('meta_key', 'thumbnail_id')
                    ->value('meta_value');

                $yoastTitle = $this->wp()
                    ->table('termmeta')
                    ->where('term_id', $cat->term_id)
                    ->where('meta_key', 'wpseo_title')
                    ->value('meta_value');

                $yoastDesc = $this->wp()
                    ->table('termmeta')
                    ->where('term_id', $cat->term_id)
                    ->where('meta_key', 'wpseo_desc')
                    ->value('meta_value');

                // Slug unique : suffixer si doublon
                $slug = $cat->slug;
                $i = 1;
                while (ProductCategory::where('slug', $slug)->exists()) {
                    $slug = $cat->slug . '-' . (++$i);
                    $this->warn("  Slug doublon pour « {$cat->name} », renommé en {$slug}");
                }

                $category = ProductCategory::create([
                    'name' => $cat->name,
                    'slug' => $slug,
                    'description' => $cat->description ?? '',
                    'featured_image_id' => $mediaMap[(int) $thumbnailId] ?? null,
                    'sort_order' => $created,
                    'meta_title' => $yoastTitle ?: null,
                    'meta_description' => $yoastDesc ?: null,
                ]);

                $map[$cat->term_id] = $category->id;
                $created++;
            } catch (\Throwable $e) {
                $errors++;
                $this->error("  Erreur sur « {$cat->name} » (term_id={$cat->term_id}) : {$e->getMessage()}");
            }
        }

        // Pass 2 : lier les parents
        $linked = 0;
        foreach ($categories as $cat) {
            if ($cat->parent && isset($map[$cat->parent], $map[$cat->term_id])) {
                ProductCategory::where('id', $map[$cat->term_id])
                    ->update(['parent_id' => $map[$cat->parent]]);
                $linked++;
            }
        }

        $this->saveMap('wp_category_map.json', $map);
        $this->printResult('Catégories', $created);
        $this->info("  Relations parent-enfant : {$linked} liées");
        if ($errors) {
            $this->error("  {$errors} erreur(s) rencontrée(s)");
        }

        return self::SUCCESS;
    }
}
