@if($item)
<div id="cart-item-{{ $item['key'] }}"
     class="flex items-start gap-4 bg-white border border-gray-100 rounded-lg p-4">

    @php $productUrl = $item['product']?->url() ?? '#'; @endphp

    {{-- Image produit --}}
    <a href="{{ $productUrl }}" class="w-16 h-16 rounded flex-shrink-0 overflow-hidden bg-gray-100 block">
        @if(!empty($item['image']))
            <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
        @else
            <div class="w-full h-full flex items-center justify-center text-gray-300">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/>
                </svg>
            </div>
        @endif
    </a>

    {{-- Details --}}
    <div class="flex-1 min-w-0">
        <a href="{{ $productUrl }}" class="text-sm font-medium text-gray-800 truncate block hover:text-brand-700 transition">{{ $item['name'] }}</a>
        @if(!empty($item['addons']))
            <ul class="mt-1 space-y-0.5">
                @foreach($item['addons'] as $addon)
                    @if(!empty($addon['value']))
                        <li class="text-xs text-gray-400">{{ $addon['label'] ?? '' }} : {{ $addon['value'] }}</li>
                    @endif
                @endforeach
            </ul>
        @endif
        @php
            $unitPrice = $item['price'] + ($item['addon_price_per_unit'] ?? 0);
        @endphp
        <p class="text-sm text-gray-500 mt-1">
            {{ number_format($unitPrice, 2, ',', ' ') }} € / unite
            @if(($item['addon_price_flat'] ?? 0) > 0)
                <span class="text-xs text-gray-400">+ {{ number_format($item['addon_price_flat'], 2, ',', ' ') }} € fixe</span>
            @endif
        </p>
    </div>

    {{-- Quantite --}}
    <form action="{{ route('cart.update', $item['key']) }}" method="POST"
          class="flex items-center gap-1" x-data="{ qty: {{ $item['quantity'] }} }" data-turbo="false">
        @csrf @method('PATCH')
        <button type="button" @click="qty = Math.max(0, qty - 1); $nextTick(() => $el.closest('form').submit())"
                class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded text-gray-600 hover:bg-gray-50 text-sm">-</button>
        <input type="number" name="quantity" x-model="qty" min="0" max="99"
               class="w-10 text-center text-sm border border-gray-200 rounded py-0.5 focus:outline-none focus:ring-1 focus:ring-brand-500"
               @change="$nextTick(() => $el.closest('form').submit())">
        <button type="button" @click="qty = Math.min(99, qty + 1); $nextTick(() => $el.closest('form').submit())"
                class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded text-gray-600 hover:bg-gray-50 text-sm">+</button>
    </form>

    {{-- Prix ligne --}}
    <div class="text-right flex-shrink-0">
        @php
            $lineTotal = ($item['price'] + ($item['addon_price_per_unit'] ?? 0)) * $item['quantity'] + ($item['addon_price_flat'] ?? 0);
        @endphp
        <p class="text-sm font-semibold text-gray-900">
            {{ number_format($lineTotal, 2, ',', ' ') }} €
        </p>
        <form action="{{ route('cart.remove', $item['key']) }}" method="POST" class="mt-1" data-turbo="false">
            @csrf @method('DELETE')
            <button type="submit" class="text-xs text-gray-400 hover:text-red-500 transition">
                Supprimer
            </button>
        </form>
    </div>

</div>
@endif
