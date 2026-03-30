<?php

namespace App\Services;

use App\Models\ProductAddon;

class AddonPriceCalculator
{
    /**
     * Calcule le prix total des addons sélectionnés.
     *
     * Trois types de prix :
     * - flat_fee : ajouté 1 fois quel que soit la quantité
     * - quantity_based : multiplié par la quantité produit
     * - percentage_based : % du prix de base du produit
     *
     * @param  array  $addons  [addonId => ['value' => '...', 'option_index' => int|null], ...]
     * @param  float  $basePrice  Prix unitaire du produit
     * @param  int  $quantity  Quantité du produit (pour quantity_based)
     * @return array{flat: float, per_unit: float, total: float}
     */
    public function calculate(array $addons, float $basePrice = 0, int $quantity = 1): array
    {
        if (empty($addons)) {
            return ['flat' => 0.0, 'per_unit' => 0.0, 'total' => 0.0];
        }

        $addonIds = array_keys($addons);
        $dbAddons = ProductAddon::whereIn('id', $addonIds)->get()->keyBy('id');

        $flatTotal = 0.0;
        $perUnitTotal = 0.0;

        foreach ($addons as $addonId => $data) {
            $dbAddon = $dbAddons->get($addonId);
            if (! $dbAddon) {
                continue;
            }

            $price = $this->resolvePrice($dbAddon, $data);

            if ($price <= 0) {
                continue;
            }

            $priceType = $this->resolvePriceType($dbAddon, $data);

            match ($priceType) {
                'flat_fee' => $flatTotal += $price,
                'quantity_based' => $perUnitTotal += $price,
                'percentage_based' => $perUnitTotal += round($basePrice * $price / 100, 2),
                default => $perUnitTotal += $price,
            };
        }

        $total = $flatTotal + ($perUnitTotal * $quantity);

        return [
            'flat' => round($flatTotal, 2),
            'per_unit' => round($perUnitTotal, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * Calcul simplifié retournant uniquement le total.
     */
    public function total(array $addons, float $basePrice = 0, int $quantity = 1): float
    {
        return $this->calculate($addons, $basePrice, $quantity)['total'];
    }

    /**
     * Résout le prix d'un addon en tenant compte des options sélectionnées.
     */
    private function resolvePrice(ProductAddon $dbAddon, array $data): float
    {
        // Pas de prix si aucune valeur soumise
        $value = is_array($data['value'] ?? null) ? implode('', $data['value']) : ($data['value'] ?? '');
        if (trim($value) === '') {
            return 0.0;
        }

        // Si l'addon a des options et qu'une option est sélectionnée
        if (! empty($dbAddon->options) && isset($data['option_index'])) {
            $option = $dbAddon->options[(int) $data['option_index']] ?? null;
            if ($option && isset($option['price']) && $option['price'] !== '') {
                return (float) $option['price'];
            }
        }

        // Si l'addon a des options et une valeur textuelle, chercher l'option par label
        if (! empty($dbAddon->options) && isset($data['value'])) {
            foreach ($dbAddon->options as $opt) {
                if (($opt['label'] ?? '') === $data['value'] && isset($opt['price']) && $opt['price'] !== '') {
                    return (float) $opt['price'];
                }
            }
        }

        // Prix direct de l'addon (si adjust_price est activé)
        if ($dbAddon->adjust_price && $dbAddon->price > 0) {
            return (float) $dbAddon->price;
        }

        return 0.0;
    }

    /**
     * Résout le type de prix en tenant compte des options.
     */
    private function resolvePriceType(ProductAddon $dbAddon, array $data): string
    {
        if (! empty($dbAddon->options) && isset($data['option_index'])) {
            $option = $dbAddon->options[(int) $data['option_index']] ?? null;
            if ($option && isset($option['price_type'])) {
                return $option['price_type'];
            }
        }

        if (! empty($dbAddon->options) && isset($data['value'])) {
            foreach ($dbAddon->options as $opt) {
                if (($opt['label'] ?? '') === $data['value'] && isset($opt['price_type'])) {
                    return $opt['price_type'];
                }
            }
        }

        return $dbAddon->price_type ?? 'flat_fee';
    }

    /**
     * Formate un addon pour l'affichage panier/commande.
     */
    public function format(array $addon): string
    {
        $label = $addon['label'] ?? '';
        $value = $addon['value'] ?? '';

        if (empty($value)) {
            return $label;
        }

        return "{$label} : {$value}";
    }
}
