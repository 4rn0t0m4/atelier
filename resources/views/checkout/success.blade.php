<x-layouts.app title="Commande confirmée" :noindex="true">
<div class="max-w-2xl mx-auto px-4 py-16 text-center">

    @if($paymentConfirmed)
        <div class="mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-2xl font-semibold text-brand-900 mb-2" style="font-family: Georgia, serif;">
                Commande confirmée !
            </h1>
            <p class="text-gray-600">
                Merci pour votre commande <strong>{{ $order->number }}</strong>.
                Vous recevrez une confirmation par e-mail à <strong>{{ $order->billing_email }}</strong>.
            </p>
        </div>
    @else
        <div class="mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-100 mb-4">
                <svg class="w-8 h-8 text-yellow-600 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-semibold text-brand-900 mb-2" style="font-family: Georgia, serif;">
                Paiement en cours de vérification
            </h1>
            <p class="text-gray-600">
                Votre commande <strong>{{ $order->number }}</strong> est en cours de traitement.
                Vous recevrez une confirmation par e-mail dès que le paiement sera validé.
            </p>
        </div>
    @endif

    <div class="bg-brand-50 rounded-xl p-6 text-left mb-8">
        <h2 class="text-sm font-semibold text-brand-900 mb-4">Détail de la commande</h2>
        <ul class="space-y-2">
            @foreach($order->items as $item)
                <li class="flex justify-between text-sm text-gray-700">
                    <span>{{ $item->product_name }} × {{ $item->quantity }}</span>
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

        <div class="border-t border-brand-200 mt-4 pt-4 flex justify-between font-semibold text-brand-900">
            <span>Total</span>
            <span>{{ number_format($order->total, 2, ',', ' ') }} €</span>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row gap-3 justify-center">
        @auth
            <a href="{{ route('account.orders') }}"
               class="inline-block py-2.5 px-6 rounded font-medium transition text-sm text-white bg-brand-700 hover:bg-brand-800">
                Voir mes commandes
            </a>
        @else
            <a href="{{ route('register') }}"
               class="inline-block py-2.5 px-6 rounded font-medium transition text-sm text-white bg-brand-700 hover:bg-brand-800">
                Créer un compte
            </a>
        @endauth
        <a href="{{ route('shop.index') }}"
           class="inline-block py-2.5 px-6 rounded font-medium transition text-sm border border-brand-200 text-brand-700 hover:opacity-70">
            Continuer les achats
        </a>
    </div>
</div>

{{-- Conversion tracking --}}
@if($paymentConfirmed)
@if(config('tracking.google_analytics_id'))
<script>
    gtag('event', 'purchase', {
        transaction_id: '{{ $order->number }}',
        value: {{ $order->total }},
        currency: 'EUR',
        items: [
            @foreach($order->items as $item)
            { item_name: '{{ addslashes($item->product_name) }}', quantity: {{ $item->quantity }}, price: {{ $item->unit_price }} },
            @endforeach
        ]
    });
</script>
@endif
@if(config('tracking.facebook_pixel_id'))
<script>
    fbq('track', 'Purchase', {
        value: {{ $order->total }},
        currency: 'EUR',
        content_ids: [{!! $order->items->pluck('product_id')->map(fn($id) => "'$id'")->join(',') !!}],
        content_type: 'product',
        num_items: {{ $order->items->sum('quantity') }}
    });
</script>
@endif
@endif

</x-layouts.app>
