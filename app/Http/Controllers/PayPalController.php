<?php

namespace App\Http\Controllers;

use App\Mail\NewOrderAdmin;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\Product;
use App\Services\CartService;
use App\Services\PayPalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PayPalController extends Controller
{
    public function __construct(
        private PayPalService $paypal,
        private CartService $cart,
    ) {}

    /**
     * Crée une commande PayPal pour une commande existante.
     */
    public function createOrder(Request $request)
    {
        $request->validate(['order_id' => 'required|integer']);

        $order = Order::where('id', $request->order_id)
            ->where('payment_method', 'paypal')
            ->where('status', 'pending')
            ->firstOrFail();

        $result = $this->paypal->createOrder($order->total, $order->number);

        if ($result['id']) {
            $order->update(['paypal_order_id' => $result['id']]);

            return response()->json(['id' => $result['id']]);
        }

        return response()->json(['error' => $result['error']], 500);
    }

    /**
     * Capture le paiement PayPal et confirme la commande.
     */
    public function captureOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'paypal_order_id' => 'required|string',
        ]);

        $order = Order::where('id', $request->order_id)
            ->where('payment_method', 'paypal')
            ->where('status', 'pending')
            ->firstOrFail();

        $result = $this->paypal->captureOrder($request->paypal_order_id);

        if (! $result['success']) {
            return response()->json(['error' => $result['error']], 500);
        }

        // Confirmer la commande
        DB::transaction(function () use ($order, $request) {
            $locked = Order::where('id', $order->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return;
            }

            $locked->update([
                'status' => 'processing',
                'paid_at' => now(),
                'paypal_order_id' => $request->paypal_order_id,
            ]);

            Log::info("Commande #{$locked->number} payée via PayPal.");

            $locked->load('items');
            foreach ($locked->items as $item) {
                $product = Product::where('id', $item->product_id)
                    ->where('manage_stock', true)
                    ->lockForUpdate()
                    ->first();

                if (! $product || $product->stock_quantity < $item->quantity) {
                    Log::warning("Stock insuffisant pour produit #{$item->product_id}", [
                        'order' => $locked->id,
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
        });

        // Envoyer les emails
        try {
            $order->refresh()->load('items.addons');
            Mail::to($order->billing_email)->send(new OrderConfirmation($order));
            Mail::to(config('mail.admin_address'))->send(new NewOrderAdmin($order));
        } catch (\Exception $e) {
            Log::error("Erreur envoi email commande #{$order->number}", ['error' => $e->getMessage()]);
        }

        // Vider le panier
        $this->cart->clear();

        return response()->json([
            'success' => true,
            'redirect' => route('checkout.success') . '?order=' . $order->id,
        ]);
    }
}
