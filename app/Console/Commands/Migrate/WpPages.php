<?php

namespace App\Console\Commands\Migrate;

use App\Models\Page;

class WpPages extends WpImportCommand
{
    protected $signature = 'migrate:wp-pages';
    protected $description = 'Importe les pages statiques depuis WordPress';

    /**
     * Pages WooCommerce à ignorer (panier, commande, compte)
     */
    private array $excludeSlugs = ['panier', 'commande', 'mon-compte'];

    public function handle(): int
    {
        $this->info('Import des pages WordPress...');
        $this->safeTruncate('pages');

        $pages = $this->wp()
            ->table('posts')
            ->where('post_type', 'page')
            ->where('post_status', 'publish')
            ->whereNotIn('post_name', $this->excludeSlugs)
            ->orderBy('menu_order')
            ->orderBy('post_title')
            ->get();

        $created = 0;

        foreach ($pages as $i => $p) {
            $meta = $this->postMeta($p->ID);

            $yoastTitle = $meta['_yoast_wpseo_title'] ?? null;
            $yoastDesc = $meta['_yoast_wpseo_metadesc'] ?? null;

            Page::create([
                'title' => $p->post_title,
                'slug' => $p->post_name,
                'content' => $p->post_content,
                'meta_title' => $yoastTitle ?: null,
                'meta_description' => $yoastDesc ?: null,
                'is_published' => true,
                'sort_order' => $p->menu_order ?: $i,
            ]);

            $created++;
        }

        $this->printResult('Pages', $created);

        return self::SUCCESS;
    }
}
