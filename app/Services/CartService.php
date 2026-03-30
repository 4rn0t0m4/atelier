<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAddon;

class CartService
{
    private const SESSION_KEY = 'cart';

    public function all(): array
    {
        return session(self::SESSION_KEY, []);
    }

    /**
     * Ajoute ou incrémente un article.
     * Clé unique : product_id + md5(serialized_addons)
     */
    public function add(Product $product, int $quantity = 1, array $addons = []): string
    {
        $cart = $this->all();

        $sanitizedAddons = $this->sanitizeAddons($addons);

        // Si un addon a sync_qty, forcer la quantité au nombre de lignes
        $quantity = $this->applySyncQty($addons, $quantity);

        // Clé unique par produit + combinaison d'addons
        $key = $product->id . '-' . md5(serialize($sanitizedAddons));

        $hasSyncQty = ProductAddon::whereIn('id', array_keys($addons))
            ->where('sync_qty', true)
            ->exists();

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] = $hasSyncQty ? $quantity : $cart[$key]['quantity'] + $quantity;
        } else {
            $calculator = app(AddonPriceCalculator::class);
            $addonCalc = $calculator->calculate($sanitizedAddons, $product->effective_price, $quantity);

            $cart[$key] = [
                'key' => $key,
                'product_id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->effective_price,
                'addon_price_flat' => $addonCalc['flat'],
                'addon_price_per_unit' => $addonCalc['per_unit'],
                'quantity' => $quantity,
                'addons' => $sanitizedAddons,
                'image' => $product->featuredImage?->url,
            ];
        }

        session([self::SESSION_KEY => $cart]);

        return $key;
    }

    /**
     * Remplace les labels/prix client par les valeurs DB de confiance.
     */
    private function sanitizeAddons(array $addons): array
    {
        if (empty($addons)) {
            return [];
        }

        $dbAddons = ProductAddon::whereIn('id', array_keys($addons))->get()->keyBy('id');
        $sanitized = [];

        foreach ($addons as $addonId => $data) {
            $dbAddon = $dbAddons->get($addonId);
            if (! $dbAddon) {
                continue;
            }

            $value = $data['value'] ?? ($data[0] ?? '');
            $valueStr = is_array($value) ? implode('', $value) : $value;

            // Ignorer les addons sans valeur
            if (trim($valueStr) === '') {
                continue;
            }

            $sanitized[$addonId] = [
                'label' => $dbAddon->label,
                'value' => $value,
                'option_index' => $data['option_index'] ?? null,
            ];
        }

        return $sanitized;
    }

    /**
     * Si un addon a sync_qty=true, force la quantité au nombre de lignes non-vides.
     */
    private function applySyncQty(array $addons, int $quantity): int
    {
        if (empty($addons)) {
            return $quantity;
        }

        $syncAddons = ProductAddon::whereIn('id', array_keys($addons))
            ->where('sync_qty', true)
            ->get();

        foreach ($syncAddons as $addon) {
            $value = $addons[$addon->id]['value'] ?? '';
            if (is_string($value) && trim($value) !== '') {
                $lines = array_filter(explode("\n", $value), fn($l) => trim($l) !== '');
                $lineCount = count($lines);
                if ($lineCount > 0) {
                    return $lineCount;
                }
            }
        }

        return $quantity;
    }

    public function updatePrice(string $key, float $newPrice): void
    {
        $cart = $this->all();

        if (isset($cart[$key])) {
            $cart[$key]['price'] = $newPrice;
            session([self::SESSION_KEY => $cart]);
        }
    }

    public function update(string $key, int $quantity): void
    {
        $cart = $this->all();

        if (isset($cart[$key])) {
            if ($quantity <= 0) {
                unset($cart[$key]);
            } else {
                $cart[$key]['quantity'] = $quantity;
            }
            session([self::SESSION_KEY => $cart]);
        }
    }

    public function remove(string $key): void
    {
        $cart = $this->all();
        unset($cart[$key]);
        session([self::SESSION_KEY => $cart]);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        return array_sum(array_column($this->all(), 'quantity'));
    }

    /**
     * Sous-total avant remises.
     * flat_fee addons : ajoutés 1 fois par ligne.
     * quantity_based/percentage addons : multipliés par la quantité.
     */
    public function subtotal(): float
    {
        return array_reduce($this->all(), function (float $carry, array $item) {
            $lineTotal = ($item['price'] + ($item['addon_price_per_unit'] ?? 0)) * $item['quantity']
                       + ($item['addon_price_flat'] ?? 0);

            return $carry + $lineTotal;
        }, 0.0);
    }

    /**
     * Calcule le total d'une ligne du panier.
     */
    public function lineTotal(array $item): float
    {
        return ($item['price'] + ($item['addon_price_per_unit'] ?? 0)) * $item['quantity']
             + ($item['addon_price_flat'] ?? 0);
    }

    /**
     * Items enrichis avec le modèle Product (pour DiscountEngine et checkout).
     */
    public function itemsWithProducts(): array
    {
        $cart = $this->all();
        if (empty($cart)) {
            return [];
        }

        $productIds = array_unique(array_column($cart, 'product_id'));
        $products = Product::with('category')->whereIn('id', $productIds)->get()->keyBy('id');

        return array_map(function (array $item) use ($products) {
            $product = $products->get($item['product_id']);
            $item['product'] = $product;
            $item['unit_price'] = $item['price'];

            return $item;
        }, $cart);
    }
}
