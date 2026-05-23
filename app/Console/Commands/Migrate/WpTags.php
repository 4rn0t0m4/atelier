<?php

namespace App\Console\Commands\Migrate;

use App\Models\Product;
use App\Models\ProductTag;

class WpTags extends WpImportCommand
{
    protected $signature = 'migrate:wp-tags';
    protected $description = 'Importe les tags produit depuis WordPress et les associe aux produits';

    public function handle(): int
    {
        $this->info('Import des tags produit...');

        $productMap = $this->loadMap('wp_product_map.json');

        if (empty($productMap)) {
            $this->error('Mapping produits introuvable. Exécutez migrate:wp-products d\'abord.');
            return self::FAILURE;
        }

        // Récupérer tous les tags WooCommerce
        $tags = $this->wp()
            ->table('terms as t')
            ->join('term_taxonomy as tt', 't.term_id', '=', 'tt.term_id')
            ->where('tt.taxonomy', 'product_tag')
            ->orderBy('t.name')
            ->get();

        $this->info("  {$tags->count()} tags trouvés dans WordPress");

        $created = 0;

        foreach ($tags as $wpTag) {
            $tag = ProductTag::firstOrCreate(
                ['slug' => $wpTag->slug],
                ['name' => $wpTag->name]
            );

            // Récupérer les produits associés à ce tag
            $wpProductIds = $this->wp()
                ->table('term_relationships as tr')
                ->join('term_taxonomy as tt', 'tr.term_taxonomy_id', '=', 'tt.term_taxonomy_id')
                ->where('tt.term_id', $wpTag->term_id)
                ->where('tt.taxonomy', 'product_tag')
                ->pluck('tr.object_id');

            $laravelProductIds = [];
            foreach ($wpProductIds as $wpId) {
                if (isset($productMap[$wpId])) {
                    $laravelProductIds[] = $productMap[$wpId];
                }
            }

            $tag->products()->syncWithoutDetaching($laravelProductIds);

            $this->info("  Tag « {$tag->name} » : {$tag->products()->count()} produit(s)");
            $created++;
        }

        $this->printResult('Tags', $created);

        return self::SUCCESS;
    }
}
