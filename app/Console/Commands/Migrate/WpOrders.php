<?php

namespace App\Console\Commands\Migrate;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAddon;
use Carbon\Carbon;

class WpOrders extends WpImportCommand
{
    protected $signature = 'migrate:wp-orders';
    protected $description = 'Importe les commandes, items et addons depuis WooCommerce (legacy storage)';

    private array $statusMap = [
        'wc-pending' => 'pending',
        'wc-processing' => 'processing',
        'wc-on-hold' => 'on-hold',
        'wc-completed' => 'completed',
        'wc-cancelled' => 'cancelled',
        'wc-refunded' => 'refunded',
        'wc-failed' => 'failed',
    ];

    public function handle(): int
    {
        $this->info('Import des commandes WooCommerce...');

        $this->safeTruncate('order_item_addons');
        $this->safeTruncate('order_items');
        $this->safeTruncate('orders');

        $userMap = $this->loadMap('wp_user_map.json');
        $productMap = $this->loadMap('wp_product_map.json');

        $wpOrders = $this->wp()
            ->table('posts')
            ->where('post_type', 'shop_order')
            ->orderBy('ID')
            ->get();

        $ordersCreated = 0;
        $itemsCreated = 0;
        $addonsCreated = 0;

        foreach ($wpOrders as $wo) {
            $meta = $this->postMeta($wo->ID);

            $status = $this->statusMap[$wo->post_status] ?? 'pending';
            $customerId = (int) ($meta['_customer_user'] ?? 0);

            // Dates
            $paidAt = ! empty($meta['_date_paid'])
                ? Carbon::createFromTimestamp((int) $meta['_date_paid'])
                : null;

            $completedAt = ! empty($meta['_date_completed'])
                ? Carbon::createFromTimestamp((int) $meta['_date_completed'])
                : null;

            // Stripe
            $stripeIntentId = $meta['_stripe_intent_id'] ?? null;

            // Tracking (WC Shipment Tracking)
            $trackingData = ! empty($meta['_wc_shipment_tracking_items'])
                ? @unserialize($meta['_wc_shipment_tracking_items'])
                : null;
            $tracking = is_array($trackingData) && ! empty($trackingData) ? $trackingData[0] : null;

            // Coupon
            $couponCode = $this->wp()
                ->table('woocommerce_order_items')
                ->where('order_id', $wo->ID)
                ->where('order_item_type', 'coupon')
                ->value('order_item_name');

            // Shipping method name
            $shippingMethod = $this->wp()
                ->table('woocommerce_order_items')
                ->where('order_id', $wo->ID)
                ->where('order_item_type', 'shipping')
                ->value('order_item_name');

            $order = new Order();
            $order->timestamps = false;
            $order->fill([
                'user_id' => $userMap[$customerId] ?? null,
                'number' => 'CMD-' . strtolower(substr(md5($wo->ID), 0, 13)),
                'status' => $status,
                'subtotal' => (float) ($meta['_order_total'] ?? 0) - (float) ($meta['_order_shipping'] ?? 0) + (float) ($meta['_cart_discount'] ?? 0),
                'discount_total' => (float) ($meta['_cart_discount'] ?? 0),
                'shipping_total' => (float) ($meta['_order_shipping'] ?? 0),
                'tax_total' => (float) ($meta['_order_tax'] ?? 0),
                'total' => (float) ($meta['_order_total'] ?? 0),
                'currency' => $meta['_order_currency'] ?? 'EUR',
                'payment_method' => $meta['_payment_method'] ?? null,
                'stripe_payment_intent_id' => $stripeIntentId,
                'paid_at' => $paidAt,
                'billing_first_name' => $meta['_billing_first_name'] ?? null,
                'billing_last_name' => $meta['_billing_last_name'] ?? null,
                'billing_email' => $meta['_billing_email'] ?? null,
                'billing_phone' => $meta['_billing_phone'] ?? null,
                'billing_address_1' => $meta['_billing_address_1'] ?? null,
                'billing_address_2' => $meta['_billing_address_2'] ?? null,
                'billing_city' => $meta['_billing_city'] ?? null,
                'billing_postcode' => $meta['_billing_postcode'] ?? null,
                'billing_country' => $meta['_billing_country'] ?? null,
                'shipping_first_name' => $meta['_shipping_first_name'] ?? null,
                'shipping_last_name' => $meta['_shipping_last_name'] ?? null,
                'shipping_address_1' => $meta['_shipping_address_1'] ?? null,
                'shipping_address_2' => $meta['_shipping_address_2'] ?? null,
                'shipping_city' => $meta['_shipping_city'] ?? null,
                'shipping_postcode' => $meta['_shipping_postcode'] ?? null,
                'shipping_country' => $meta['_shipping_country'] ?? null,
                'shipping_method' => $shippingMethod,
                'tracking_number' => $tracking['tracking_number'] ?? null,
                'tracking_carrier' => $tracking['tracking_provider'] ?? null,
                'tracking_url' => $tracking['custom_tracking_link'] ?? null,
                'shipped_at' => isset($tracking['date_shipped']) && $tracking['date_shipped']
                    ? Carbon::createFromTimestamp((int) $tracking['date_shipped'])
                    : null,
                'coupon_code' => $couponCode,
                'customer_note' => $wo->post_excerpt ?: null,
            ]);
            $order->created_at = $wo->post_date;
            $order->updated_at = $wo->post_modified;
            $order->save();

            $ordersCreated++;

            // --- Order Items (line_item) ---
            $wpItems = $this->wp()
                ->table('woocommerce_order_items')
                ->where('order_id', $wo->ID)
                ->where('order_item_type', 'line_item')
                ->get();

            foreach ($wpItems as $wi) {
                $itemMeta = $this->wp()
                    ->table('woocommerce_order_itemmeta')
                    ->where('order_item_id', $wi->order_item_id)
                    ->pluck('meta_value', 'meta_key')
                    ->toArray();

                $wpProductId = (int) ($itemMeta['_product_id'] ?? 0);
                $qty = (int) ($itemMeta['_qty'] ?? 1);
                $lineTotal = (float) ($itemMeta['_line_total'] ?? 0);
                $lineTax = (float) ($itemMeta['_line_tax'] ?? 0);

                // Calculer le prix unitaire et prix addons
                $unitPrice = $qty > 0 ? $lineTotal / $qty : $lineTotal;

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $productMap[$wpProductId] ?? null,
                    'product_name' => $wi->order_item_name,
                    'sku' => $itemMeta['_sku'] ?? null,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'addons_price' => 0, // recalculé après extraction des addons
                    'total' => $lineTotal,
                    'tax' => $lineTax,
                ]);
                $itemsCreated++;

                // --- Addon values from _pao_ids or non-underscore meta ---
                $addonsPrice = 0;

                // Méthode 1 : _pao_ids (structuré)
                $paoRaw = $itemMeta['_pao_ids'] ?? null;
                $paoData = $paoRaw ? @unserialize($paoRaw) : null;

                if (is_array($paoData) && ! empty($paoData)) {
                    foreach ($paoData as $pao) {
                        $label = $pao['key'] ?? '';
                        $value = $pao['value'] ?? '';

                        // Extraire le prix du label s'il est entre parenthèses : "Dimensions (0,20 €)"
                        $addonPrice = 0;
                        if (preg_match('/\((\d+[.,]\d+)\s*€\)/', $label, $m)) {
                            $addonPrice = (float) str_replace(',', '.', $m[1]);
                            $label = trim(preg_replace('/\s*\(\d+[.,]\d+\s*€\)/', '', $label));
                        }

                        OrderItemAddon::create([
                            'order_item_id' => $orderItem->id,
                            'addon_label' => $label,
                            'addon_value' => $value,
                            'addon_price' => $addonPrice,
                            'addon_price_type' => 'flat_fee',
                        ]);
                        $addonsPrice += $addonPrice;
                        $addonsCreated++;
                    }
                } else {
                    // Méthode 2 : meta non-underscore = valeurs addon brutes
                    foreach ($itemMeta as $key => $value) {
                        if (str_starts_with($key, '_') || in_array($key, ['method_id', 'instance_id', 'cost', 'total_tax', 'taxes', 'Articles'])) {
                            continue;
                        }

                        $addonPrice = 0;
                        $cleanLabel = $key;
                        if (preg_match('/\((\d+[.,]\d+)\s*€\)/', $key, $m)) {
                            $addonPrice = (float) str_replace(',', '.', $m[1]);
                            $cleanLabel = trim(preg_replace('/\s*\(\d+[.,]\d+\s*€\)/', '', $key));
                        }

                        OrderItemAddon::create([
                            'order_item_id' => $orderItem->id,
                            'addon_label' => $cleanLabel,
                            'addon_value' => $value,
                            'addon_price' => $addonPrice,
                            'addon_price_type' => 'flat_fee',
                        ]);
                        $addonsPrice += $addonPrice;
                        $addonsCreated++;
                    }
                }

                if ($addonsPrice > 0) {
                    $orderItem->update(['addons_price' => $addonsPrice]);
                }
            }
        }

        $this->printResult('Commandes', $ordersCreated);
        $this->printResult('Items', $itemsCreated);
        $this->printResult('Addons items', $addonsCreated);

        return self::SUCCESS;
    }
}
