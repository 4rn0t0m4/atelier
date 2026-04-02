<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class OrderFulfillmentService
{
    /**
     * Confirme le paiement d'une commande (pending → processing) et décrémente le stock.
     * Utilise un lock pessimiste pour éviter les doubles traitements.
     *
     * @return bool true si la commande a été confirmée, false si déjà traitée
     */
    public function confirmPayment(Order $order): bool
    {
        $locked = Order::where('id', $order->id)
            ->where('status', 'pending')
            ->lockForUpdate()
            ->first();

        if (! $locked) {
            return false;
        }

        $locked->update([
            'status' => 'processing',
            'paid_at' => now(),
        ]);

        $locked->load('items');
        $this->decrementStock($locked);

        return true;
    }

    /**
     * Décrémente le stock pour chaque ligne de commande.
     */
    public function decrementStock(Order $order): void
    {
        foreach ($order->items as $item) {
            $product = Product::where('id', $item->product_id)
                ->where('manage_stock', true)
                ->lockForUpdate()
                ->first();

            if (! $product || $product->stock_quantity < $item->quantity) {
                Log::warning("Stock insuffisant pour produit #{$item->product_id}", [
                    'order' => $order->id,
                    'requested' => $item->quantity,
                    'available' => $product?->stock_quantity,
                ]);

                continue;
            }

            $product->decrement('stock_quantity', $item->quantity);
            $product->increment('total_sales', $item->quantity);

            if ($product->fresh()->stock_quantity <= 0) {
                $product->update(['stock_status' => 'outofstock']);
            }
        }
    }
}
