<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Génère le fichier sitemap.xml dans /public';

    public function handle(): void
    {
        $urls = [];

        // Accueil
        $urls[] = $this->url(url('/'), now(), 'daily', '1.0');

        // Boutique
        $urls[] = $this->url(url('/boutique'), now(), 'daily', '0.9');

        // Catégories
        $categories = ProductCategory::with('parent')->orderBy('name')->get();
        foreach ($categories as $category) {
            $urls[] = $this->url($category->url(), $category->updated_at, 'weekly', '0.8');
        }

        // Produits actifs
        $products = Product::with(['category.parent'])
            ->where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->get();

        foreach ($products as $product) {
            $urls[] = $this->url($product->url(), $product->updated_at, 'weekly', '0.7');
        }

        // Pages publiées
        $pages = Page::where('is_published', true)->orderBy('title')->get();
        foreach ($pages as $page) {
            $urls[] = $this->url(url("/{$page->slug}"), $page->updated_at, 'monthly', '0.5');
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $entry) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$entry['loc']}</loc>\n";
            if ($entry['lastmod']) {
                $xml .= "    <lastmod>{$entry['lastmod']}</lastmod>\n";
            }
            $xml .= "    <changefreq>{$entry['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$entry['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        file_put_contents(public_path('sitemap.xml'), $xml);

        $this->info("Sitemap généré — " . count($urls) . " URLs.");
    }

    private function url(string $loc, ?Carbon $lastmod, string $changefreq, string $priority): array
    {
        return [
            'loc' => htmlspecialchars($loc, ENT_XML1),
            'lastmod' => $lastmod?->toDateString(),
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }
}
