<x-layouts.app :title="'Commande ' . $order->number" :noindex="true">
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="flex items-center gap-3 mb-8">
        <a href="{{ route('account.orders') }}" class="text-gray-400 hover:text-gray-600 text-sm">Mes commandes</a>
        <span class="text-gray-300">/</span>
        <h1 class="text-2xl font-semibold text-brand-900" style="font-family: Georgia, serif;">{{ $order->number }}</h1>
        @include('account.partials.order-status', ['status' => $order->status])
    </div>

    {{-- Articles --}}
    <section class="bg-white border border-brand-100 rounded-xl overflow-hidden mb-6">
        <h2 class="text-sm font-semibold text-gray-700 px-5 py-4 border-b border-brand-100">Articles</h2>
        <div class="divide-y divide-gray-50">
            @foreach($order->items as $item)
                <div class="px-5 py-4">
                    <div class="flex justify-between text-sm">
                        <span class="font-medium text-gray-900">{{ $item->product_name }}</span>
                        <span class="text-gray-700">{{ number_format($item->total, 2, ',', ' ') }} €</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">Qté : {{ $item->quantity }} × {{ number_format($item->unit_price, 2, ',', ' ') }} €</p>
                    @if($item->addons->isNotEmpty())
                        <ul class="mt-2 space-y-0.5">
                            @foreach($item->addons as $addon)
                                <li class="text-xs text-gray-500">
                                    <span class="font-medium">{{ $addon->addon_label }} :</span>
                                    {{ $addon->addon_value }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="px-5 py-4 border-t border-brand-100 space-y-1 text-sm">
            <div class="flex justify-between text-gray-600">
                <span>Sous-total</span>
                <span>{{ number_format($order->subtotal, 2, ',', ' ') }} €</span>
            </div>
            @if($order->discount_total > 0)
                <div class="flex justify-between text-green-700">
                    <span>Remise</span>
                    <span>−{{ number_format($order->discount_total, 2, ',', ' ') }} €</span>
                </div>
            @endif
            @if($order->shipping_total > 0)
                <div class="flex justify-between text-gray-600">
                    <span>{{ $order->shipping_method }}</span>
                    <span>{{ number_format($order->shipping_total, 2, ',', ' ') }} €</span>
                </div>
            @elseif($order->shipping_method)
                <div class="flex justify-between text-gray-600">
                    <span>{{ $order->shipping_method }}</span>
                    <span>Gratuit</span>
                </div>
            @endif
            <div class="flex justify-between font-semibold text-brand-900 pt-2 border-t border-brand-100">
                <span>Total</span>
                <span>{{ number_format($order->total, 2, ',', ' ') }} €</span>
            </div>
        </div>
    </section>

    {{-- Adresses --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <section class="bg-white border border-brand-100 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">Adresse de facturation</h2>
            <address class="text-sm text-gray-600 not-italic leading-relaxed">
                {{ $order->billing_first_name }} {{ $order->billing_last_name }}<br>
                {{ $order->billing_address_1 }}
                @if($order->billing_address_2)<br>{{ $order->billing_address_2 }}@endif
                <br>{{ $order->billing_postcode }} {{ $order->billing_city }}<br>
                {{ config('shipping.countries.' . $order->billing_country, $order->billing_country) }}
            </address>
        </section>

        <section class="bg-white border border-brand-100 rounded-xl p-5">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">Adresse de livraison</h2>
            <address class="text-sm text-gray-600 not-italic leading-relaxed">
                {{ $order->shipping_first_name }} {{ $order->shipping_last_name }}<br>
                {{ $order->shipping_address_1 }}
                @if($order->shipping_address_2)<br>{{ $order->shipping_address_2 }}@endif
                <br>{{ $order->shipping_postcode }} {{ $order->shipping_city }}<br>
                {{ config('shipping.countries.' . $order->shipping_country, $order->shipping_country) }}
            </address>
        </section>
    </div>

    {{-- Suivi --}}
    @if($order->tracking_number)
        <section class="bg-blue-50 border border-blue-100 rounded-xl p-5 text-sm text-blue-800 mb-6">
            <strong>Suivi :</strong> {{ $order->tracking_carrier }} — {{ $order->tracking_number }}
        </section>
    @endif

    {{-- Note client --}}
    @if($order->customer_note)
        <section class="bg-brand-50 border border-brand-100 rounded-xl p-5 text-sm text-brand-800">
            <strong>Note :</strong> {{ $order->customer_note }}
        </section>
    @endif
</div>
</x-layouts.app>
