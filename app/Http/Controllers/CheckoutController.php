<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutStoreRequest;
use App\Mail\NewOrderAdmin;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Services\CartService;
use App\Services\DiscountEngine;
use App\Services\OrderService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cart,
        private DiscountEngine $discount,
        private OrderService $orderService,
    ) {}

    public function index()
    {
        if ($this->dailyLimitReached()) {
            return redirect()->route('cart.index')
                ->with('warnings', ['Nous avons atteint notre limite de commandes pour aujourd\'hui. Merci de réessayer demain !']);
        }

        $items = $this->cart->itemsWithProducts();

        if (empty($items)) {
            return redirect()->route('cart.index');
        }

        $subtotal = $this->cart->subtotal();
        $discount = $this->discount->calculate($items, $subtotal);
        $shippingMethods = config('shipping.methods');
        $shippingCountries = config('shipping.countries');
        $shippingZones = config('shipping.zones');

        $prefill = [];
        if ($user = auth()->user()) {
            if ($user->first_name && $user->address_1) {
                $prefill = [
                    'billing_first_name' => $user->first_name,
                    'billing_last_name' => $user->last_name,
                    'billing_email' => $user->email,
                    'billing_phone' => $user->phone,
                    'billing_address_1' => $user->address_1,
                    'billing_address_2' => $user->address_2,
                    'billing_city' => $user->city,
                    'billing_postcode' => $user->postcode,
                    'billing_country' => $user->country,
                ];
                if ($user->shipping_address_1) {
                    $prefill['shipping_same'] = false;
                    $prefill['shipping_first_name'] = $user->shipping_first_name;
                    $prefill['shipping_last_name'] = $user->shipping_last_name;
                    $prefill['shipping_address_1'] = $user->shipping_address_1;
                    $prefill['shipping_address_2'] = $user->shipping_address_2;
                    $prefill['shipping_city'] = $user->shipping_city;
                    $prefill['shipping_postcode'] = $user->shipping_postcode;
                    $prefill['shipping_country'] = $user->shipping_country;
                }
            } else {
                $lastOrder = $user->orders()->latest()->first();
                if ($lastOrder) {
                    $prefill = [
                        'billing_first_name' => $lastOrder->billing_first_name,
                        'billing_last_name' => $lastOrder->billing_last_name,
                        'billing_email' => $lastOrder->billing_email,
                        'billing_phone' => $lastOrder->billing_phone,
                        'billing_address_1' => $lastOrder->billing_address_1,
                        'billing_address_2' => $lastOrder->billing_address_2,
                        'billing_city' => $lastOrder->billing_city,
                        'billing_postcode' => $lastOrder->billing_postcode,
                        'billing_country' => $lastOrder->billing_country,
                    ];
                } else {
                    $nameParts = explode(' ', $user->name, 2);
                    $prefill = [
                        'billing_first_name' => $nameParts[0] ?? '',
                        'billing_last_name' => $nameParts[1] ?? '',
                        'billing_email' => $user->email,
                    ];
                }
            }
        }

        return view('checkout.index', compact('items', 'subtotal', 'discount', 'shippingMethods', 'shippingCountries', 'shippingZones', 'prefill'));
    }

    public function store(CheckoutStoreRequest $request)
    {
        if ($this->dailyLimitReached()) {
            return redirect()->route('cart.index')
                ->with('warnings', ['Nous avons atteint notre limite de commandes pour aujourd\'hui. Merci de réessayer demain !']);
        }

        $items = $this->cart->itemsWithProducts();

        if (empty($items)) {
            return redirect()->route('cart.index');
        }

        $stockCheck = $this->orderService->validateCartStock($items);

        if (! empty($stockCheck['errors'])) {
            return redirect()->route('cart.index')->with('warnings', $stockCheck['errors']);
        }

        if ($stockCheck['cartUpdated']) {
            return redirect()->route('cart.index')
                ->with('warnings', ['Les prix de certains produits ont changé. Veuillez vérifier votre panier.']);
        }

        $items = $this->cart->itemsWithProducts();
        $subtotal = $this->cart->subtotal();
        $discount = $this->discount->calculate($items, $subtotal, $request->input('coupon_code'));

        $shippingKey = $request->shipping_method;
        $shippingSame = $request->boolean('shipping_same', false);
        $shippingCountry = $shippingSame ? $request->billing_country : ($request->shipping_country ?? $request->billing_country);
        $shippingCost = $this->orderService->calculateShipping($shippingKey, $subtotal, $shippingCountry);

        if ($discount['free_shipping']) {
            $shippingCost = 0;
        }

        $total = max(0, $subtotal - $discount['amount'] + $shippingCost);

        $customerNote = $this->orderService->buildCustomerNote(
            $request->customer_note,
            $shippingKey,
            $request->relay_point_name,
            $request->relay_point_address,
        );

        // Création de compte si demandé
        if (! auth()->check() && $request->boolean('create_account') && $request->filled('password')) {
            $existing = User::where('email', $request->billing_email)->first();

            if (! $existing) {
                $user = User::create([
                    'name' => $request->billing_first_name . ' ' . $request->billing_last_name,
                    'email' => $request->billing_email,
                    'password' => $request->password,
                    'first_name' => $request->billing_first_name,
                    'last_name' => $request->billing_last_name,
                    'phone' => $request->billing_phone,
                    'address_1' => $request->billing_address_1,
                    'address_2' => $request->billing_address_2,
                    'city' => $request->billing_city,
                    'postcode' => $request->billing_postcode,
                    'country' => $request->billing_country,
                ]);

                Auth::login($user);
            }
        }

        $paymentMethod = $request->input('payment_method', 'stripe');

        $orderData = [
            'user_id' => auth()->id(),
            'number' => 'CMD-' . substr(md5(uniqid()), 0, 13),
            'status' => 'pending',
            'billing_first_name' => $request->billing_first_name,
            'billing_last_name' => $request->billing_last_name,
            'billing_email' => $request->billing_email,
            'billing_phone' => $request->billing_phone,
            'billing_address_1' => $request->billing_address_1,
            'billing_address_2' => $request->billing_address_2,
            'billing_city' => $request->billing_city,
            'billing_postcode' => $request->billing_postcode,
            'billing_country' => $request->billing_country,
            'shipping_first_name' => $shippingSame ? $request->billing_first_name : $request->shipping_first_name,
            'shipping_last_name' => $shippingSame ? $request->billing_last_name : $request->shipping_last_name,
            'shipping_address_1' => $shippingSame ? $request->billing_address_1 : $request->shipping_address_1,
            'shipping_address_2' => $shippingSame ? $request->billing_address_2 : $request->shipping_address_2,
            'shipping_city' => $shippingSame ? $request->billing_city : $request->shipping_city,
            'shipping_postcode' => $shippingSame ? $request->billing_postcode : $request->shipping_postcode,
            'shipping_country' => $shippingSame ? $request->billing_country : $request->shipping_country,
            'subtotal' => $subtotal,
            'discount_total' => $discount['amount'],
            'shipping_total' => $shippingCost,
            'shipping_method' => config("shipping.methods.{$shippingKey}.label"),
            'shipping_key' => $shippingKey,
            'relay_point_code' => in_array($shippingKey, ['boxtal', 'boxtal_intl']) ? $request->relay_point_code : null,
            'relay_network' => in_array($shippingKey, ['boxtal', 'boxtal_intl']) ? $request->relay_network : null,
            'tax_total' => 0,
            'total' => $total,
            'customer_note' => $customerNote,
            'coupon_code' => $request->coupon_code,
            'currency' => 'EUR',
            'payment_method' => $paymentMethod,
        ];

        if ($paymentMethod === 'paypal') {
            $order = $this->orderService->createOrder($orderData, $items);

            return view('checkout.payment', [
                'order' => $order,
                'paymentMethod' => 'paypal',
                'paypalClientId' => config('services.paypal.client_id'),
            ]);
        }

        // Stripe (défaut)
        $paymentIntent = $this->createPaymentIntent($total, $request->billing_email);
        $orderData['stripe_payment_intent_id'] = $paymentIntent->id;

        $order = $this->orderService->createOrder($orderData, $items);

        Stripe::setApiKey(config('cashier.secret'));
        PaymentIntent::update($paymentIntent->id, [
            'metadata' => ['order_id' => $order->id],
            'description' => "Commande #{$order->number}",
        ]);

        return view('checkout.payment', [
            'order' => $order,
            'paymentMethod' => 'stripe',
            'clientSecret' => $paymentIntent->client_secret,
            'stripeKey' => config('cashier.key'),
        ]);
    }

    public function success(Request $request)
    {
        // PayPal : la commande est déjà confirmée par le captureOrder, on la retrouve par ID
        if ($request->query('order')) {
            $order = Order::find($request->query('order'));
        } else {
            // Stripe : retrouver par payment_intent
            $order = Order::where('stripe_payment_intent_id', $request->query('payment_intent'))->first();
        }

        if (! $order) {
            return redirect()->route('shop.index');
        }

        // Vérifier que l'utilisateur est le propriétaire de la commande
        abort_if(auth()->check() && $order->user_id && $order->user_id !== auth()->id(), 403);

        $paymentConfirmed = in_array($order->status, ['processing', 'completed']);

        // Vérification Stripe uniquement si pas encore confirmé
        if (! $paymentConfirmed && $order->status === 'pending' && $order->stripe_payment_intent_id) {
            Stripe::setApiKey(config('cashier.secret'));
            $intent = PaymentIntent::retrieve($order->stripe_payment_intent_id);

            if ($intent->status === 'succeeded') {
                $paymentConfirmed = true;
                $this->confirmOrderFromSuccess($order);

                try {
                    $order->load('items.addons');
                    Mail::to($order->billing_email)->send(new OrderConfirmation($order));
                    Mail::to(config('mail.admin_address'))->send(new NewOrderAdmin($order));
                } catch (\Exception $e) {
                    Log::error("Erreur envoi email commande #{$order->number}", ['error' => $e->getMessage()]);
                }
            }
        }

        if ($paymentConfirmed) {
            $this->cart->clear();
        }

        return view('checkout.success', [
            'order' => $order->load('items'),
            'paymentConfirmed' => $paymentConfirmed,
        ]);
    }

    private function confirmOrderFromSuccess(Order $order): void
    {
        DB::transaction(function () use ($order) {
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
            ]);

            Log::info("Commande #{$locked->number} confirmée via page success.");

            $locked->load('items');
            foreach ($locked->items as $item) {
                $product = Product::where('id', $item->product_id)
                    ->where('manage_stock', true)
                    ->lockForUpdate()
                    ->first();

                if ($product && $product->stock_quantity >= $item->quantity) {
                    $product->decrement('stock_quantity', $item->quantity);
                    $product->increment('total_sales', $item->quantity);
                    if ($product->fresh()->stock_quantity <= 0) {
                        $product->update(['stock_status' => 'outofstock']);
                    }
                }
            }
        });
    }

    private function dailyLimitReached(): bool
    {
        $limit = (int) Setting::get('daily_order_limit', 0);

        if ($limit <= 0) {
            return false;
        }

        $todayCount = Order::whereDate('created_at', today())
            ->whereIn('status', ['processing', 'completed', 'shipped'])
            ->count();

        return $todayCount >= $limit;
    }

    private function createPaymentIntent(float $total, string $email): PaymentIntent
    {
        Stripe::setApiKey(config('cashier.secret'));

        return PaymentIntent::create([
            'amount' => (int) round($total * 100),
            'currency' => 'eur',
            'automatic_payment_methods' => ['enabled' => true],
            'receipt_email' => $email,
        ]);
    }
}
