<?php

namespace App\Http\Controllers;

use App\Mail\NewOrderAdmin;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderFulfillmentService;
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
     * Logge une erreur PayPal côté client.
     */
    public function logError(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'error' => 'required|string|max:2000',
            'context' => 'nullable|string|max:500',
        ]);

        Log::warning('PayPal erreur client', [
            'order_id' => $request->order_id,
            'user_id' => auth()->id(),
            'error' => $request->error,
            'context' => $request->context,
        ]);

        return response()->json(['logged' => true]);
    }

    /**
     * Crée une commande PayPal pour une commande existante.
     */
    public function createOrder(Request $request)
    {
        $request->validate(['order_id' => 'required|integer']);

        $order = Order::where('id', $request->order_id)
            ->where('user_id', auth()->id())
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
            ->where('user_id', auth()->id())
            ->where('payment_method', 'paypal')
            ->where('status', 'pending')
            ->firstOrFail();

        $result = $this->paypal->captureOrder($request->paypal_order_id);

        if (! $result['success']) {
            return response()->json(['error' => $result['error']], 500);
        }

        // Confirmer la commande
        DB::transaction(function () use ($order, $request) {
            $order->update(['paypal_order_id' => $request->paypal_order_id]);

            $fulfillment = app(OrderFulfillmentService::class);
            $fulfillment->confirmPayment($order);

            Log::info("Commande #{$order->number} payée via PayPal.");
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
