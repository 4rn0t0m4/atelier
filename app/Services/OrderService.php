<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private CartService $cart,
    ) {}

    /**
     * Vérifie stock et prix actuels pour tous les articles du panier.
     *
     * @return array{ok: bool, errors: string[], cartUpdated: bool}
     */
    public function validateCartStock(array $items): array
    {
        $errors = [];
        $cartUpdated = false;

        foreach ($items as $key => $item) {
            $product = $item['product'];

            if (! $product || ! $product->is_active) {
                $this->cart->remove($key);
                $errors[] = "{$item['name']} n'est plus disponible.";

                continue;
            }

            if (abs($product->effective_price - $item['price']) > 0.01) {
                $this->cart->updatePrice($key, $product->effective_price);
                $cartUpdated = true;
            }

            if ($product->manage_stock && $product->stock_quantity < $item['quantity']) {
                if ($product->stock_quantity <= 0) {
                    $this->cart->remove($key);
                    $errors[] = "{$item['name']} est en rupture de stock.";
                } else {
                    $this->cart->update($key, $product->stock_quantity);
                    $errors[] = "{$item['name']} : quantité réduite à {$product->stock_quantity} (stock insuffisant).";
                }
            }
        }

        return [
            'ok' => empty($errors) && ! $cartUpdated,
            'errors' => $errors,
            'cartUpdated' => $cartUpdated,
        ];
    }

    /**
     * Calcule le coût de livraison.
     */
    public function calculateShipping(string $shippingKey, float $subtotal, string $country = 'FR', array $cartItems = []): float
    {
        $allLight = ! empty($cartItems) && collect($cartItems)->every(fn ($item) => $item['product']?->light_shipping);

        $lightPrice = config("shipping.methods.{$shippingKey}.light_price");
        $cost = ($allLight && $lightPrice !== null)
            ? (float) $lightPrice
            : (float) config("shipping.methods.{$shippingKey}.price", 0);

        $zone = $this->getShippingZone($country);
        $threshold = config("shipping.zones.{$zone}.free_shipping_threshold");

        if ($threshold && config("shipping.methods.{$shippingKey}.free_above_threshold") && $subtotal >= $threshold) {
            $cost = 0;
        }

        return $cost;
    }

    /**
     * Retourne la clé de zone pour un pays donné.
     */
    public function getShippingZone(string $country): string
    {
        foreach (config('shipping.zones', []) as $zoneKey => $zone) {
            if (in_array($country, $zone['countries'] ?? [])) {
                return $zoneKey;
            }
        }

        return 'FR';
    }

    /**
     * Retourne les méthodes de livraison disponibles pour un pays donné.
     */
    public function availableMethodsForCountry(string $country): array
    {
        $zone = $this->getShippingZone($country);
        $allowedKeys = config("shipping.zones.{$zone}.methods", ['colissimo']);
        $allMethods = config('shipping.methods');

        return array_intersect_key($allMethods, array_flip($allowedKeys));
    }

    /**
     * Construit la note client avec infos point relais si applicable.
     */
    public function buildCustomerNote(?string $note, string $shippingKey, ?string $relayName, ?string $relayAddress): ?string
    {
        $customerNote = $note ?? '';

        if (in_array($shippingKey, ['boxtal', 'boxtal_intl']) && $relayName) {
            $relayInfo = "Point relais : {$relayName}";
            if ($relayAddress) {
                $relayInfo .= " — {$relayAddress}";
            }
            $customerNote = $relayInfo . ($customerNote ? "\n\n" . $customerNote : '');
        }

        return $customerNote ?: null;
    }

    /**
     * Crée la commande et ses lignes dans une transaction.
     */
    public function createOrder(array $orderData, array $cartItems): Order
    {
        return DB::transaction(function () use ($orderData, $cartItems) {
            $order = Order::create($orderData);

            foreach ($cartItems as $item) {
                $addonPricePerUnit = $item['addon_price_per_unit'] ?? 0;
                $addonPriceFlat = $item['addon_price_flat'] ?? 0;
                $lineTotal = ($item['price'] + $addonPricePerUnit) * $item['quantity'] + $addonPriceFlat;

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'addons_price' => $addonPricePerUnit * $item['quantity'] + $addonPriceFlat,
                    'total' => $lineTotal,
                    'tax' => 0,
                ]);

                if (! empty($item['addons'])) {
                    foreach ($item['addons'] as $addonId => $addonData) {
                        $label = is_array($addonData) ? ($addonData['label'] ?? $addonId) : $addonId;
                        $value = is_array($addonData) ? ($addonData['value'] ?? '') : (string) $addonData;

                        $orderItem->addons()->create([
                            'addon_label' => $label,
                            'addon_value' => is_array($value) ? implode(', ', $value) : (string) $value,
                            'addon_price' => 0,
                            'addon_price_type' => 'flat_fee',
                        ]);
                    }
                }

                // Stock décrémenté après paiement (webhook / page success)
            }

            return $order;
        });
    }
}
