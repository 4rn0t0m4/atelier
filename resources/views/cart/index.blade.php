<x-layouts.app title="Mon panier">

{{-- GA4 view_cart --}}
@if(config('tracking.google_analytics_id') && !empty($items))
<script>
    gtag('event', 'view_cart', {
        currency: 'EUR',
        value: {{ $subtotal }},
        items: [
            @foreach($items as $item)
            { item_id: '{{ $item['product']->sku ?: $item['product']->id }}', item_name: '{{ addslashes($item['product']->name) }}', price: {{ $item['price'] }}, quantity: {{ $item['quantity'] }} },
            @endforeach
        ]
    });
</script>
@endif

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-2xl font-semibold text-gray-900 mb-8">Mon panier</h1>

    <div id="cart-flash"></div>

    @if(session('warnings'))
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
            @foreach(session('warnings') as $warning)
                <p class="text-sm text-amber-800">{{ $warning }}</p>
            @endforeach
        </div>
    @endif

    @if(empty($items))
        <div class="text-center py-16 text-gray-400">
            <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                      d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="text-lg mb-4">Votre panier est vide</p>
            <a href="{{ route('shop.index') }}"
               class="inline-block bg-brand-600 text-white px-6 py-2.5 rounded font-medium hover:opacity-90 transition">
                Continuer mes achats
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- Articles --}}
            <div class="lg:col-span-2 space-y-3">
                @foreach($items as $key => $item)
                    @include('cart.partials.item', ['item' => $item])
                @endforeach
            </div>

            {{-- Recap commande --}}
            <div class="lg:col-span-1">
                <div class="bg-gray-50 rounded-xl p-6 space-y-4 sticky top-24">
                    <h2 class="font-semibold text-gray-900">Recapitulatif</h2>

                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Sous-total</span>
                        <span id="cart-subtotal">{{ number_format($subtotal, 2, ',', ' ') }} €</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-400">
                        <span>Livraison</span>
                        <span>Calculee a l'etape suivante</span>
                    </div>
                    <div class="border-t border-gray-200 pt-4 flex justify-between font-semibold text-gray-900">
                        <span>Total</span>
                        <span>{{ number_format($subtotal, 2, ',', ' ') }} €</span>
                    </div>

                    <a href="{{ route('checkout.index') }}"
                       class="block w-full text-center bg-brand-600 text-white py-3 rounded-lg font-medium hover:bg-brand-700 transition">
                        Passer commande
                    </a>
                    <a href="{{ route('shop.index') }}"
                       class="block w-full text-center text-sm text-gray-500 hover:text-brand-700 transition">
                        Continuer mes achats
                    </a>
                </div>
            </div>

        </div>
    @endif

    {{-- Suggestions --}}
    @if($suggestions->isNotEmpty())
    <div class="mt-16">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">Vous aimerez peut-etre</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @foreach($suggestions as $product)
            <a href="{{ $product->url() }}" class="group block bg-white rounded-xl border border-gray-100 hover:border-brand-200 hover:shadow-sm transition overflow-hidden">
                <div class="aspect-square bg-gray-50 overflow-hidden">
                    @if($product->featuredImage)
                        <img src="{{ $product->featuredImage->url }}"
                             alt="{{ $product->featuredImage->alt ?: $product->name }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-300">
                            <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                    @endif
                </div>
                <div class="p-3">
                    <p class="text-sm font-medium text-gray-800 line-clamp-2 group-hover:text-brand-700 transition">{{ $product->name }}</p>
                    <p class="mt-1 text-sm font-semibold text-brand-700">{{ number_format($product->effective_price, 2, ',', ' ') }} €</p>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
</x-layouts.app>
