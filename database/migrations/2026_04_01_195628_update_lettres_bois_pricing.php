<?php

use App\Models\Product;
use App\Models\ProductAddon;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Prix par lettre pour chaque addon "Taille des lettres", indexé par addon ID.
     * Extrait des labels existants (ex: "8 cm (5€ par lettre suppl.)").
     */
    private array $addonPrices = [
        // Addon 11 — Lettres en bois (cat "Lettres en bois")
        11 => [5, 6, 7, 8],
        // Addon 35 — Lettres en bois relief (cat "Lettre en bois relief")
        35 => [5, 8, 12, 15, 19],
        // Addon 78 — Lettre en bois naturel (produit 30)
        78 => [3, 4, 5, 6, 7, 12, 19],
        // Addon 234 — Grand lettre en bois (produit 75)
        234 => [10],
        // Addon 301 — Lettre en bois en relief - Fleurs & Printemps
        301 => [6, 9, 12],
        // Addon 305 — Cake Topper en bois - Lettre florale
        305 => [5, 8, 12],
        // Addon 362 — Lettre en bois en relief - Mer & Océan
        362 => [6, 9, 12],
    ];

    public function up(): void
    {
        // Mettre les prix par lettre sur chaque addon "Taille des lettres"
        foreach ($this->addonPrices as $addonId => $prices) {
            $taille = ProductAddon::find($addonId);
            if (! $taille) continue;

            $options = $taille->options;
            foreach ($options as $i => &$opt) {
                $opt['price'] = $prices[$i] ?? 0;
                $opt['price_type'] = 'quantity_based';
            }
            $taille->options = $options;
            $taille->save();
        }

        // Prix de base → 0€ pour les catégories lettres en bois
        $categories = ['Lettres en bois', 'Lettre en bois relief'];
        foreach ($categories as $catName) {
            $categoryId = \App\Models\ProductCategory::where('name', $catName)->value('id');
            if ($categoryId) {
                Product::where('category_id', $categoryId)->update(['price' => 0]);
            }
        }
    }

    public function down(): void
    {
        // Remettre les prix addon à 0
        foreach (array_keys($this->addonPrices) as $addonId) {
            $taille = ProductAddon::find($addonId);
            if (! $taille) continue;

            $options = $taille->options;
            foreach ($options as &$opt) {
                $opt['price'] = 0;
            }
            $taille->options = $options;
            $taille->save();
        }

        // Restaurer les prix de base
        $restore = ['Lettres en bois' => 5, 'Lettre en bois relief' => 5];
        foreach ($restore as $catName => $price) {
            $categoryId = \App\Models\ProductCategory::where('name', $catName)->value('id');
            if ($categoryId) {
                Product::where('category_id', $categoryId)->update(['price' => $price]);
            }
        }
    }
};
