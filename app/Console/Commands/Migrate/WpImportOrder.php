<?php

namespace App\Console\Commands\Migrate;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemAddon;
use Carbon\Carbon;

class WpImportOrder extends WpImportCommand
{
    protected $signature = 'migrate:wp-order {id : ID WordPress de la commande}';
    protected $description = 'Importe une seule commande WooCommerce par son ID WordPress (sans truncate)';

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
        $wpId = (int) $this->argument('id');

        $wo = $this->wp()
            ->table('posts')
            ->where('ID', $wpId)
            ->where('post_type', 'shop_order')
            ->first();

        if (! $wo) {
            $this->error("Commande WP #{$wpId} introuvable.");
            return self::FAILURE;
        }

        // Vérifier si déjà importée (même numéro)
        $number = 'CMD-' . strtolower(substr(md5($wo->ID), 0, 13));
        $existing = Order::where('number', $number)->first();
        if ($existing) {
            $this->warn("Commande déjà importée : {$number} (ID Laravel: {$existing->id})");
            return self::SUCCESS;
        }

        $userMap = $this->loadMap('wp_user_map.json');
        $productMap = $this->loadMap('wp_product_map.json');

        $meta = $this->postMeta($wo->ID);
        $status = $this->statusMap[$wo->post_status] ?? 'pending';
        $customerId = (int) ($meta['_customer_user'] ?? 0);

        $paidAt = ! empty($meta['_date_paid'])
            ? Carbon::createFromTimestamp((int) $meta['_date_paid'])
            : null;

        $completedAt = ! empty($meta['_date_completed'])
            ? Carbon::createFromTimestamp((int) $meta['_date_completed'])
            : null;

        $stripeIntentId = $meta['_stripe_intent_id'] ?? null;

        $trackingData = ! empty($meta['_wc_shipment_tracking_items'])
            ? @unserialize($meta['_wc_shipment_tracking_items'])
            : null;
        $tracking = is_array($trackingData) && ! empty($trackingData) ? $trackingData[0] : null;

        $couponCode = $this->wp()
            ->table('woocommerce_order_items')
            ->where('order_id', $wo->ID)
            ->where('order_item_type', 'coupon')
            ->value('order_item_name');

        $shippingMethod = $this->wp()
            ->table('woocommerce_order_items')
            ->where('order_id', $wo->ID)
            ->where('order_item_type', 'shipping')
            ->value('order_item_name');

        $order = new Order();
        $order->timestamps = false;
        $order->fill([
            'user_id' => $userMap[$customerId] ?? null,
            'number' => $number,
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

        $this->info("Commande {$number} créée (ID Laravel: {$order->id})");

        // --- Order Items ---
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
            $unitPrice = $qty > 0 ? $lineTotal / $qty : $lineTotal;

            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $productMap[$wpProductId] ?? null,
                'product_name' => $wi->order_item_name,
                'sku' => $itemMeta['_sku'] ?? null,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'addons_price' => 0,
                'total' => $lineTotal,
                'tax' => $lineTax,
            ]);

            $this->info("  Item: {$wi->order_item_name} x{$qty}");

            // --- Addons ---
            $addonsPrice = 0;
            $paoRaw = $itemMeta['_pao_ids'] ?? null;
            $paoData = $paoRaw ? @unserialize($paoRaw) : null;

            if (is_array($paoData) && ! empty($paoData)) {
                foreach ($paoData as $pao) {
                    $label = $pao['key'] ?? '';
                    $value = $pao['value'] ?? '';
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
                }
            } else {
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
                }
            }

            if ($addonsPrice > 0) {
                $orderItem->update(['addons_price' => $addonsPrice]);
            }
        }

        $this->info("Terminé — {$wpItems->count()} item(s) importé(s).");

        return self::SUCCESS;
    }
}
