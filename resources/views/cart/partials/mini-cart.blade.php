<div class="p-5">
    @if(empty($items))
        <div class="text-center py-10">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="text-sm text-gray-500 mb-4">Votre panier est vide</p>
            <a href="{{ route('shop.index') }}"
               class="inline-block text-sm font-medium px-4 py-2 rounded-lg bg-brand-600 text-white hover:bg-brand-700 transition">
                Parcourir la boutique
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($items as $item)
                @php
                    $productUrl = $item['product']?->url() ?? '#';
                    $unitPrice = $item['price'] + ($item['addon_price_per_unit'] ?? 0);
                    $lineTotal = $unitPrice * $item['quantity'] + ($item['addon_price_flat'] ?? 0);
                @endphp
                <a href="{{ $productUrl }}" class="flex items-center gap-3 group">
                    <div class="w-14 h-14 rounded-lg flex-shrink-0 overflow-hidden bg-gray-100">
                        @if(!empty($item['image']))
                            <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-300">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-800 leading-snug group-hover:text-brand-700 transition">{{ $item['name'] }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $item['quantity'] }} &times; {{ number_format($unitPrice, 2, ',', ' ') }} &euro;</p>
                    </div>
                    <p class="text-sm font-medium text-gray-900 flex-shrink-0">
                        {{ number_format($lineTotal, 2, ',', ' ') }} &euro;
                    </p>
                </a>
            @endforeach
        </div>

        <div class="border-t pt-4 mt-4 space-y-3 border-gray-200">
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Sous-total</span>
                <span class="text-base font-semibold text-gray-900">{{ number_format($subtotal, 2, ',', ' ') }} &euro;</span>
            </div>
            <a href="{{ route('cart.index') }}"
               class="block text-center text-sm font-semibold py-2.5 rounded-lg bg-brand-600 text-white hover:bg-brand-700 transition">
                Voir le panier
            </a>
        </div>
    @endif
</div>
