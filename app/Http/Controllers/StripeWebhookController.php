<?php

namespace App\Http\Controllers;

use App\Mail\NewOrderAdmin;
use App\Mail\OrderConfirmation;
use App\Mail\PaymentFailed;
use App\Models\Order;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Stripe::setApiKey(config('cashier.secret'));

        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('cashier.webhook.secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature invalid', ['error' => $e->getMessage()]);

            return response('Invalid signature', 400);
        }

        match ($event->type) {
            'payment_intent.succeeded' => $this->handlePaymentSucceeded($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event->data->object),
            default => null,
        };

        return response('OK', 200);
    }

    private function handlePaymentSucceeded(object $paymentIntent): void
    {
        $order = DB::transaction(function () use ($paymentIntent) {
            $order = Order::where('stripe_payment_intent_id', $paymentIntent->id)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                Log::error('Stripe webhook: order not found', ['payment_intent_id' => $paymentIntent->id]);

                return null;
            }

            if ($order->status !== 'pending') {
                return null;
            }

            $fulfillment = app(OrderFulfillmentService::class);
            $fulfillment->confirmPayment($order);

            Log::info("Commande #{$order->number} payée via Stripe.");

            return $order->fresh();
        });

        if ($order) {
            $order->load('items.addons');

            try {
                Mail::to($order->billing_email)->send(new OrderConfirmation($order));
                Mail::to(config('mail.admin_address'))->send(new NewOrderAdmin($order));
            } catch (\Exception $e) {
                Log::error("Erreur envoi email commande #{$order->number}", ['error' => $e->getMessage()]);
            }
        }
    }

    private function handlePaymentFailed(object $paymentIntent): void
    {
        $order = Order::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (! $order) {
            return;
        }

        $order->update(['status' => 'failed']);
        Log::warning("Commande #{$order->number} paiement échoué.");

        try {
            Mail::to($order->billing_email)->send(new PaymentFailed($order));
        } catch (\Exception $e) {
            Log::error("Erreur envoi email échec paiement #{$order->number}", ['error' => $e->getMessage()]);
        }
    }
}
