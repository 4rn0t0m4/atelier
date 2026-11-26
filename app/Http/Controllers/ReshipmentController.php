<?php

namespace App\Http\Controllers;

use App\Models\OrderReshipment;
use App\Services\PayPalService;
use App\Services\ReshipmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Stripe\Stripe;

/**
 * Page publique de reexpedition : le client choisit un nouveau point relais
 * et regle les frais (Stripe ou PayPal) via le lien recu par email.
 */
class ReshipmentController extends Controller
{
    public function __construct(
        private ReshipmentService $reshipments,
        private PayPalService $paypal,
    ) {}

    public function show(string $token)
    {
        $reshipment = $this->findByToken($token);
        $order = $reshipment->order;

        return view('reshipment.show', [
            'reshipment' => $reshipment,
            'order' => $order,
            'country' => $order->shipping_country ?: $order->billing_country ?: 'FR',
            'countryLabel' => config('shipping.countries.' . ($order->shipping_country ?: $order->billing_country ?: 'FR'), ''),
            'relayZip' => $order->shipping_postcode ?: $order->billing_postcode,
            'relayCity' => $order->shipping_city ?: $order->billing_city,
        ]);
    }

    public function store(Request $request, string $token)
    {
        $reshipment = $this->findByToken($token);

        if (! $reshipment->isPending()) {
            return redirect()->route('reshipment.show', $token);
        }

        $validated = $request->validate([
            'relay_point_code' => 'required|string|max:100',
            'relay_point_name' => 'required|string|max:255',
            'relay_point_address' => 'nullable|string|max:500',
            'relay_network' => 'nullable|string|max:50',
            'payment_method' => 'required|in:stripe,paypal',
        ], [
            'relay_point_code.required' => 'Veuillez sélectionner un point relais.',
            'relay_point_name.required' => 'Veuillez sélectionner un point relais.',
        ]);

        $this->reshipments->setRelayPoint($reshipment, [
            'code' => $validated['relay_point_code'],
            'name' => $validated['relay_point_name'],
            'address' => $validated['relay_point_address'] ?? null,
            'network' => $validated['relay_network'] ?? null,
        ], $validated['payment_method']);

        $order = $reshipment->order;

        if ($validated['payment_method'] === 'paypal') {
            return view('reshipment.payment', [
                'reshipment' => $reshipment,
                'order' => $order,
                'paymentMethod' => 'paypal',
                'paypalClientId' => config('services.paypal.client_id'),
            ]);
        }

        Stripe::setApiKey(config('cashier.secret'));

        $paymentIntent = PaymentIntent::create([
            'amount' => (int) round($reshipment->amount * 100),
            'currency' => 'eur',
            'automatic_payment_methods' => ['enabled' => true],
            'receipt_email' => $order->billing_email,
            'description' => "Réexpédition commande #{$order->number}",
            'metadata' => ['order_id' => $order->id, 'reshipment_id' => $reshipment->id],
        ]);

        $reshipment->update(['stripe_payment_intent_id' => $paymentIntent->id]);

        return view('reshipment.payment', [
            'reshipment' => $reshipment,
            'order' => $order,
            'paymentMethod' => 'stripe',
            'clientSecret' => $paymentIntent->client_secret,
            'stripeKey' => config('cashier.key'),
        ]);
    }

    /**
     * Retour apres paiement : confirme cote Stripe si le webhook n'est pas encore passe.
     */
    public function success(Request $request, string $token)
    {
        $reshipment = $this->findByToken($token);

        if ($reshipment->isPending()
            && $reshipment->payment_method === 'stripe'
            && $reshipment->stripe_payment_intent_id
            && $request->query('payment_intent') === $reshipment->stripe_payment_intent_id) {
            Stripe::setApiKey(config('cashier.secret'));
            $intent = PaymentIntent::retrieve($reshipment->stripe_payment_intent_id);

            if ($intent->status === 'succeeded') {
                $this->reshipments->confirmPayment($reshipment);
                $reshipment->refresh();
            }
        }

        return view('reshipment.success', [
            'reshipment' => $reshipment,
            'order' => $reshipment->order,
        ]);
    }

    public function paypalCreateOrder(string $token)
    {
        $reshipment = $this->findByToken($token);

        abort_unless($reshipment->isPending() && $reshipment->payment_method === 'paypal', 403);

        $result = $this->paypal->createOrder((float) $reshipment->amount, $reshipment->order->number . '-REEXP');

        if ($result['id']) {
            $reshipment->update(['paypal_order_id' => $result['id']]);

            return response()->json(['id' => $result['id']]);
        }

        return response()->json(['error' => $result['error']], 500);
    }

    public function paypalCaptureOrder(Request $request, string $token)
    {
        $request->validate(['paypal_order_id' => 'required|string']);

        $reshipment = $this->findByToken($token);

        abort_unless($reshipment->isPending() && $reshipment->payment_method === 'paypal', 403);
        abort_unless($request->paypal_order_id === $reshipment->paypal_order_id, 403);

        $result = $this->paypal->captureOrder($request->paypal_order_id);

        if (! $result['success']) {
            Log::warning("PayPal réexpédition #{$reshipment->order->number} : capture échouée", ['error' => $result['error']]);

            return response()->json(['error' => $result['error']], 500);
        }

        $this->reshipments->confirmPayment($reshipment);

        return response()->json([
            'success' => true,
            'redirect' => route('reshipment.success', $token) . '?paypal=1',
        ]);
    }

    private function findByToken(string $token): OrderReshipment
    {
        return OrderReshipment::with('order')
            ->where('token', $token)
            ->firstOrFail();
    }
}
