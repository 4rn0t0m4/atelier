<x-layouts.app title="Paiement de la réexpédition" :noindex="true">

<div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <h1 class="text-2xl font-semibold text-brand-900 mb-2" style="font-family: Georgia, serif;">Paiement</h1>
    <p class="text-sm text-gray-500 mb-8">Réexpédition de la commande n°{{ $order->number }}</p>

    <div class="bg-brand-50 rounded-xl p-5 mb-8 text-sm">
        <p class="text-gray-700"><span class="font-semibold">Point relais choisi :</span> {{ $reshipment->relay_point_name }}</p>
        <p class="text-gray-500 text-xs mt-1">{{ $reshipment->relay_point_address }}</p>
        <p class="mt-2">
            <a href="{{ route('reshipment.show', $reshipment->token) }}" class="text-xs text-brand-700 underline">Changer de point relais</a>
        </p>

        <div class="border-t border-brand-200 mt-4 pt-3 flex justify-between font-semibold text-brand-900">
            <span>Total</span>
            <span>{{ $reshipment->formatted_amount }}</span>
        </div>
    </div>

    @if($paymentMethod === 'paypal')

        <div id="paypal-container">
            <div id="paypal-button-container"></div>
            <div id="paypal-errors" class="hidden mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700"></div>
            <p class="mt-4 text-xs text-gray-400 text-center">Paiement sécurisé via PayPal</p>
        </div>

        <script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency=EUR&locale=fr_FR"></script>
        <script>
        (function() {
            var csrfToken = '{{ csrf_token() }}';
            var errorsDiv = document.getElementById('paypal-errors');

            function showError(msg) {
                errorsDiv.textContent = msg;
                errorsDiv.classList.remove('hidden');
                setTimeout(function() { errorsDiv.classList.add('hidden'); }, 8000);
            }

            function post(url, body) {
                return fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify(body || {})
                }).then(function(res) { return res.json(); });
            }

            paypal.Buttons({
                style: { layout: 'vertical', color: 'gold', shape: 'rect', label: 'pay', height: 45 },
                createOrder: function() {
                    return post('{{ route('reshipment.paypal.create-order', $reshipment->token) }}').then(function(data) {
                        if (data.error) { showError(data.error); throw new Error(data.error); }
                        return data.id;
                    });
                },
                onApprove: function(data) {
                    return post('{{ route('reshipment.paypal.capture-order', $reshipment->token) }}', { paypal_order_id: data.orderID })
                        .then(function(data) {
                            if (data.error) { showError(data.error); return; }
                            window.location.href = data.redirect;
                        })
                        .catch(function() { showError('Une erreur est survenue lors de la capture du paiement.'); });
                },
                onError: function() { showError('Une erreur est survenue avec PayPal. Veuillez réessayer.'); },
                onCancel: function() { showError('Paiement annulé.'); }
            }).render('#paypal-button-container');
        })();
        </script>

    @else

        <form id="payment-form" data-turbo="false">
            <div id="payment-element" class="mb-6"></div>
            <div id="payment-errors" class="hidden mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700"></div>

            <button id="pay-btn" type="submit"
                    class="w-full bg-brand-700 text-white py-3 px-6 rounded font-medium hover:bg-brand-800 transition text-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                <span id="pay-btn-text">Payer {{ $reshipment->formatted_amount }}</span>
                <span id="pay-btn-spinner" class="hidden">
                    <svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </span>
            </button>
            <p class="mt-4 text-xs text-gray-400 text-center">Paiement sécurisé par carte bancaire</p>
        </form>

        <script src="https://js.stripe.com/v3/"></script>
        <script>
        (function() {
            @php
                $paymentConfig = [
                'stripeKey' => $stripeKey,
                'clientSecret' => $clientSecret,
                'returnUrl' => route('reshipment.success', $reshipment->token),
                'billing' => [
                    'name' => trim($order->billing_first_name . ' ' . $order->billing_last_name),
                    'email' => $order->billing_email,
                    'phone' => $order->billing_phone ?? '',
                    'address' => [
                        'line1' => $order->billing_address_1 ?? '',
                        'line2' => $order->billing_address_2 ?? '',
                        'city' => $order->billing_city ?? '',
                        'postal_code' => $order->billing_postcode ?? '',
                        'country' => $order->billing_country ?? 'FR',
                    ],
                ],
                ];
            @endphp
            var config = @json($paymentConfig);

            var stripe = Stripe(config.stripeKey);
            var elements = stripe.elements({
                clientSecret: config.clientSecret,
                locale: 'fr',
                appearance: { theme: 'stripe', variables: { colorPrimary: '#92400e', borderRadius: '6px', fontFamily: 'system-ui, -apple-system, sans-serif' } }
            });
            elements.create('payment').mount('#payment-element');

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
                    confirmParams: { return_url: config.returnUrl, payment_method_data: { billing_details: config.billing } }
                }).then(function(result) {
                    if (result.error) { showError(result.error.message); setLoading(false); }
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
