<?php

namespace App\Services;

use App\Models\DiscountRule;

class DiscountEngine
{
    /**
     * Calcule la remise totale pour un panier.
     *
     * @param  array  $cartItems  Array depuis CartService::itemsWithProducts()
     * @param  float  $subtotal  Sous-total panier avant remise
     * @return array{amount: float, label: ?string, rules: array, free_shipping: bool}
     */
    public function calculate(array $cartItems, float $subtotal, ?string $couponCode = null): array
    {
        $rules = DiscountRule::active()->orderBy('sort_order')->get();

        $totalDiscount = 0.0;
        $appliedRules = [];
        $freeShipping = false;

        foreach ($rules as $rule) {
            if (! $this->applies($rule, $cartItems, $subtotal, $couponCode)) {
                continue;
            }

            $discount = $this->computeDiscount($rule, $cartItems, $subtotal);

            if ($discount > 0) {
                $totalDiscount += $discount;
                $appliedRules[] = [
                    'name' => $rule->name,
                    'type' => $rule->type,
                    'discount' => round($discount, 2),
                ];
            }

            if ($rule->free_shipping) {
                $freeShipping = true;
            }

            if (! $rule->stackable) {
                break;
            }
        }

        return [
            'amount' => round(min($totalDiscount, $subtotal), 2),
            'label' => implode(', ', array_column($appliedRules, 'name')) ?: null,
            'rules' => $appliedRules,
            'free_shipping' => $freeShipping,
        ];
    }

    public function validateCoupon(string $code): ?DiscountRule
    {
        return DiscountRule::active()
            ->whereNotNull('coupon_code')
            ->whereRaw('UPPER(coupon_code) = ?', [strtoupper(trim($code))])
            ->first();
    }

    private function applies(DiscountRule $rule, array $items, float $subtotal, ?string $couponCode): bool
    {
        if ($rule->coupon_code) {
            if (! $couponCode || strtoupper(trim($couponCode)) !== strtoupper($rule->coupon_code)) {
                return false;
            }
        }

        if ($rule->min_cart_value !== null && $subtotal < $rule->min_cart_value) {
            return false;
        }
        if ($rule->max_cart_value !== null && $subtotal > $rule->max_cart_value) {
            return false;
        }

        if ($rule->min_quantity !== null || $rule->max_quantity !== null) {
            $totalQty = 0;
            $hasTargets = ! empty($rule->target_categories) || ! empty($rule->target_products);

            foreach ($items as $item) {
                if ($hasTargets) {
                    $product = $item['product'] ?? null;
                    if (! $product) {
                        continue;
                    }
                    $matches = (! empty($rule->target_products) && in_array($product->id, $rule->target_products))
                            || (! empty($rule->target_categories) && in_array($product->category_id, $rule->target_categories));
                    if (! $matches) {
                        continue;
                    }
                }
                $totalQty += $item['quantity'];
            }

            if ($rule->min_quantity !== null && $totalQty < $rule->min_quantity) {
                return false;
            }
            if ($rule->max_quantity !== null && $totalQty > $rule->max_quantity) {
                return false;
            }
        }

        if (! empty($rule->target_categories) || ! empty($rule->target_products)) {
            $hasMatch = false;
            foreach ($items as $item) {
                $product = $item['product'] ?? null;
                if (! $product) {
                    continue;
                }
                if (! empty($rule->target_products) && in_array($product->id, $rule->target_products)) {
                    $hasMatch = true;
                    break;
                }
                if (! empty($rule->target_categories) && in_array($product->category_id, $rule->target_categories)) {
                    $hasMatch = true;
                    break;
                }
            }
            if (! $hasMatch) {
                return false;
            }
        }

        if ($rule->exclude_sale_items) {
            $allOnSale = true;
            foreach ($items as $item) {
                $product = $item['product'] ?? null;
                if ($product && ! $product->sale_price) {
                    $allOnSale = false;
                    break;
                }
            }
            if ($allOnSale) {
                return false;
            }
        }

        return true;
    }

    private function computeDiscount(DiscountRule $rule, array $items, float $subtotal): float
    {
        $base = $subtotal;

        if (! empty($rule->target_categories) || ! empty($rule->target_products)) {
            $base = 0.0;
            foreach ($items as $item) {
                $product = $item['product'] ?? null;
                if (! $product) {
                    continue;
                }
                $matches = (! empty($rule->target_products) && in_array($product->id, $rule->target_products))
                        || (! empty($rule->target_categories) && in_array($product->category_id, $rule->target_categories));
                if ($matches) {
                    $unitTotal = ($item['unit_price'] ?? $item['price']) + ($item['addon_price_per_unit'] ?? 0);
                    $base += $unitTotal * $item['quantity'] + ($item['addon_price_flat'] ?? 0);
                }
            }
        }

        if ($rule->discount_type === 'percentage') {
            return round($base * ((float) $rule->discount_amount / 100), 2);
        }

        return round(min((float) $rule->discount_amount, $base), 2);
    }
}
