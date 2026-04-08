<x-layouts.app title="Paiement" :noindex="true">

{{-- GA4 add_payment_info --}}
@if(config('tracking.google_analytics_id'))
<script>
    gtag('event', 'add_payment_info', {
        currency: 'EUR',
        value: {{ $order->total }},
        payment_type: '{{ $paymentMethod }}',
        items: [
            @foreach($order->items as $item)
            { item_id: '{{ addslashes($item->sku ?: $item->product_id) }}', item_name: '{{ addslashes($item->product_name) }}', price: {{ $item->unit_price }}, quantity: {{ $item->quantity }} },
            @endforeach
        ]
    });
</script>
@endif

<div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <h1 class="text-2xl font-semibold text-brand-900 mb-2" style="font-family: Georgia, serif;">Paiement</h1>
    <p class="text-sm text-gray-500 mb-8">Commande n°{{ $order->number }}</p>

    {{-- Récapitulatif --}}
    <div class="bg-brand-50 rounded-xl p-5 mb-8">
        <ul class="space-y-2">
            @foreach($order->items as $item)
                <li class="flex justify-between text-sm text-gray-700">
                    <span>{{ $item->product_name }} <span class="text-gray-400">× {{ $item->quantity }}</span></span>
                    <span>{{ number_format($item->total, 2, ',', ' ') }} €</span>
                </li>
            @endforeach
        </ul>

        @if($order->discount_total > 0)
            <div class="border-t border-brand-200 mt-3 pt-3 flex justify-between text-sm text-green-700">
                <span>Remise</span>
                <span>−{{ number_format($order->discount_total, 2, ',', ' ') }} €</span>
            </div>
        @endif

        @if($order->shipping_total > 0)
            <div class="flex justify-between text-sm text-gray-600 mt-2">
                <span>{{ $order->shipping_method }}</span>
                <span>{{ number_format($order->shipping_total, 2, ',', ' ') }} €</span>
            </div>
        @elseif($order->shipping_method)
            <div class="flex justify-between text-sm text-gray-600 mt-2">
                <span>{{ $order->shipping_method }}</span>
                <span>Gratuit</span>
            </div>
        @endif

        <div class="border-t border-brand-200 mt-3 pt-3 flex justify-between font-semibold text-brand-900">
            <span>Total</span>
            <span>{{ number_format($order->total, 2, ',', ' ') }} €</span>
        </div>
    </div>

    @if($paymentMethod === 'paypal')

        {{-- Paiement PayPal --}}
        <div id="paypal-container">
            <div id="paypal-button-container"></div>

            <div id="paypal-errors" class="hidden mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700"></div>

            <p class="mt-4 text-xs text-gray-400 text-center flex items-center justify-center gap-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Paiement sécurisé via PayPal
            </p>
        </div>

        <script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency=EUR&locale=fr_FR"></script>
        <script>
        (function() {
            var orderId = {{ $order->id }};
            var csrfToken = '{{ csrf_token() }}';
            var errorsDiv = document.getElementById('paypal-errors');

            function showError(msg) {
                errorsDiv.textContent = msg;
                errorsDiv.classList.remove('hidden');
                setTimeout(function() { errorsDiv.classList.add('hidden'); }, 8000);
            }

            function logPayPalError(error, context) {
                fetch('{{ route('paypal.log-error') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        error: String(error),
                        context: context
                    })
                }).catch(function() {});
            }

            paypal.Buttons({
                style: {
                    layout: 'vertical',
                    color: 'gold',
                    shape: 'rect',
                    label: 'pay',
                    height: 45
                },
                createOrder: function() {
                    return fetch('{{ route('paypal.create-order') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ order_id: orderId })
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.error) {
                            logPayPalError(data.error, 'createOrder');
                            showError(data.error);
                            throw new Error(data.error);
                        }
                        return data.id;
                    })
                    .catch(function(err) {
                        logPayPalError(err.message || err, 'createOrder:fetch');
                        throw err;
                    });
                },
                onApprove: function(data) {
                    return fetch('{{ route('paypal.capture-order') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            order_id: orderId,
                            paypal_order_id: data.orderID
                        })
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.error) {
                            logPayPalError(data.error, 'captureOrder');
                            showError(data.error);
                            return;
                        }
                        window.location.href = data.redirect;
                    })
                    .catch(function(err) {
                        logPayPalError(err.message || err, 'captureOrder:fetch');
                        showError('Une erreur est survenue lors de la capture du paiement.');
                    });
                },
                onError: function(err) {
                    logPayPalError(err.message || String(err), 'onError');
                    showError('Une erreur est survenue avec PayPal. Veuillez réessayer.');
                },
                onCancel: function() {
                    logPayPalError('Paiement annulé par le client', 'onCancel');
                    showError('Paiement annulé.');
                }
            }).render('#paypal-button-container');
        })();
        </script>

    @else

        {{-- Formulaire de paiement Stripe --}}
        <form id="payment-form" data-turbo="false">
            <div id="payment-element" class="mb-6"></div>

            <div id="payment-errors" class="hidden mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700"></div>

            <button id="pay-btn" type="submit"
                    class="w-full bg-brand-700 text-white py-3 px-6 rounded font-medium hover:bg-brand-800 transition text-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                <span id="pay-btn-text">Payer {{ number_format($order->total, 2, ',', ' ') }} €</span>
                <span id="pay-btn-spinner" class="hidden">
                    <svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </span>
            </button>

            <p class="mt-4 text-xs text-gray-400 text-center flex items-center justify-center gap-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                Paiement sécurisé par carte bancaire
            </p>
        </form>

        <script src="https://js.stripe.com/v3/"></script>
        <script>
        (function() {
            @php
                $paymentConfig = [
                    'stripeKey'    => $stripeKey,
                    'clientSecret' => $clientSecret,
                    'returnUrl'    => route('checkout.success'),
                    'billing'      => [
                        'name'    => trim($order->billing_first_name . ' ' . $order->billing_last_name),
                        'email'   => $order->billing_email,
                        'phone'   => $order->billing_phone ?? '',
                        'address' => [
                            'line1'       => $order->billing_address_1 ?? '',
                            'line2'       => $order->billing_address_2 ?? '',
                            'city'        => $order->billing_city ?? '',
                            'postal_code' => $order->billing_postcode ?? '',
                            'country'     => $order->billing_country ?? 'FR',
                        ],
                    ],
                ];
            @endphp
            var config = @json($paymentConfig);

            var stripe = Stripe(config.stripeKey);
            var elements = stripe.elements({
                clientSecret: config.clientSecret,
                locale: 'fr',
                appearance: {
                    theme: 'stripe',
                    variables: {
                        colorPrimary: '#92400e',
                        borderRadius: '6px',
                        fontFamily: 'system-ui, -apple-system, sans-serif'
                    }
                }
            });

            var paymentElement = elements.create('payment');
            paymentElement.mount('#payment-element');

            var form = document.getElementById('payment-form');
            var btn = document.getElementById('pay-btn');
            var btnText = document.getElementById('pay-btn-text');
            var btnSpinner = document.getElementById('pay-btn-spinner');
            var errorsDiv = document.getElementById('payment-errors');

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                setLoading(true);

                stripe.confirmPayment({
                    elements: elements,
                    confirmParams: {
                        return_url: config.returnUrl,
                        payment_method_data: {
                            billing_details: config.billing
                        }
                    }
                }).then(function(result) {
                    if (result.error) {
                        showError(result.error.message);
                        setLoading(false);
                    }
                });
            });

            function setLoading(loading) {
                btn.disabled = loading;
                btnText.classList.toggle('hidden', loading);
                btnSpinner.classList.toggle('hidden', !loading);
            }

            function showError(msg) {
                errorsDiv.textContent = msg;
                errorsDiv.classList.remove('hidden');
                setTimeout(function() { errorsDiv.classList.add('hidden'); }, 8000);
            }
        })();
        </script>

    @endif

</div>
</x-layouts.app>
