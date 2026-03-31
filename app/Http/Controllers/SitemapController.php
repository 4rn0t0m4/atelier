<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addHours(6), function () {
            return $this->generate();
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    private function generate(): string
    {
        $urls = [];

        // Accueil
        $urls[] = $this->url(url('/'), now(), 'daily', '1.0');

        // Boutique
        $urls[] = $this->url(url('/boutique'), now(), 'daily', '0.9');

        // Catégories
        foreach (ProductCategory::with('parent')->orderBy('name')->get() as $category) {
            $urls[] = $this->url($category->url(), $category->updated_at, 'weekly', '0.8');
        }

        // Produits actifs
        $products = Product::with('category.parent')
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->get();

        foreach ($products as $product) {
            $urls[] = $this->url($product->url(), $product->updated_at, 'weekly', '0.7');
        }

        // Pages publiées
        foreach (Page::where('is_published', true)->orderBy('title')->get() as $page) {
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

        return $xml;
    }

    private function url(string $loc, $lastmod, string $changefreq, string $priority): array
    {
        return [
            'loc' => htmlspecialchars($loc, ENT_XML1),
            'lastmod' => $lastmod?->toDateString(),
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }
}
