@extends('admin.layouts.app')

@section('content')
    <x-admin.page-breadcrumb title="Commande {{ $order->number }}" :breadcrumbs="['Commandes' => route('admin.orders.index'), $order->number => null]" />

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Left column: items + addresses --}}
        <div class="xl:col-span-2 space-y-6">
            {{-- Items --}}
            <div class="rounded-2xl border border-gray-200 bg-white">
                <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-800">Articles</h3>
                    <span class="text-sm text-gray-500">{{ $order->items->count() }} article(s)</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="px-5 py-3 text-left text-sm font-medium text-gray-500">Produit</th>
                                <th class="px-5 py-3 text-right text-sm font-medium text-gray-500">Prix unit.</th>
                                <th class="px-5 py-3 text-right text-sm font-medium text-gray-500">Qté</th>
                                <th class="px-5 py-3 text-right text-sm font-medium text-gray-500">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="px-5 py-4">
                                        <div class="text-sm font-medium text-gray-700">{{ $item->product_name }}</div>
                                        @if($item->sku)
                                            <div class="text-xs text-gray-400">SKU: {{ $item->sku }}</div>
                                        @endif
                                        @foreach($item->addons as $addon)
                                            <div class="text-xs text-gray-500 mt-0.5">
                                                + {{ $addon->addon_label }}: {{ $addon->addon_value }}
                                                @if($addon->addon_price > 0)
                                                    ({{ number_format($addon->addon_price, 2, ',', ' ') }} &euro;)
                                                @endif
                                            </div>
                                        @endforeach
                                    </td>
                                    <td class="px-5 py-4 text-sm text-right text-gray-600">
                                        {{ number_format($item->unit_price, 2, ',', ' ') }} &euro;
                                    </td>
                                    <td class="px-5 py-4 text-sm text-right text-gray-600">
                                        {{ $item->quantity }}
                                    </td>
                                    <td class="px-5 py-4 text-sm text-right font-medium text-gray-700">
                                        {{ number_format($item->total, 2, ',', ' ') }} &euro;
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Totals --}}
                <div class="px-5 py-4 border-t border-gray-200 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Sous-total</span>
                        <span class="text-gray-700">{{ number_format($order->subtotal, 2, ',', ' ') }} &euro;</span>
                    </div>
                    @if($order->discount_total > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-green-600">Remise{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</span>
                            <span class="text-green-600">-{{ number_format($order->discount_total, 2, ',', ' ') }} &euro;</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Livraison ({{ $order->shipping_method ?? '-' }})</span>
                        <span class="text-gray-700">
                            @if($order->shipping_total > 0)
                                {{ number_format($order->shipping_total, 2, ',', ' ') }} &euro;
                            @else
                                Gratuit
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between text-base font-semibold pt-2 border-t border-gray-100">
                        <span class="text-gray-800">Total</span>
                        <span class="text-gray-800">{{ number_format($order->total, 2, ',', ' ') }} &euro;</span>
                    </div>
                </div>
            </div>

            {{-- Addresses --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                {{-- Billing --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3">Adresse de facturation</h3>
                    <div class="text-sm text-gray-600 space-y-1">
                        <p class="font-medium">{{ $order->billing_first_name }} {{ $order->billing_last_name }}</p>
                        <p>{{ $order->billing_address_1 }}</p>
                        @if($order->billing_address_2)<p>{{ $order->billing_address_2 }}</p>@endif
                        <p>{{ $order->billing_postcode }} {{ $order->billing_city }}</p>
                        <p>{{ $order->billing_country }}</p>
                        @if($order->billing_phone)<p class="pt-1">Tel: {{ $order->billing_phone }}</p>@endif
                        <p class="pt-1">{{ $order->billing_email }}</p>
                    </div>
                </div>

                {{-- Shipping --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3">Adresse de livraison</h3>
                    <div class="text-sm text-gray-600 space-y-1">
                        @if($order->shipping_address_1)
                            <p class="font-medium">{{ $order->shipping_first_name }} {{ $order->shipping_last_name }}</p>
                            <p>{{ $order->shipping_address_1 }}</p>
                            @if($order->shipping_address_2)<p>{{ $order->shipping_address_2 }}</p>@endif
                            <p>{{ $order->shipping_postcode }} {{ $order->shipping_city }}</p>
                            <p>{{ $order->shipping_country }}</p>
                        @else
                            <p class="text-gray-400 italic">Identique a la facturation</p>
                        @endif
                        @if($order->relay_point_code)
                            <p class="pt-2 text-brand-600 font-medium">Point relais: {{ $order->relay_point_code }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Customer note --}}
            @if($order->customer_note)
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">Note du client</h3>
                    <p class="text-sm text-gray-600">{{ $order->customer_note }}</p>
                </div>
            @endif
        </div>

        {{-- Right column: status, payment, actions --}}
        <div class="space-y-6">
            {{-- Status & actions --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h3 class="text-sm font-semibold text-gray-800 mb-4">Statut & actions</h3>

                <form action="{{ route('admin.orders.update', $order) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Statut</label>
                        <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                            @foreach(['pending' => 'Non reglée', 'processing' => 'En cours', 'shipped' => 'Expédiée', 'completed' => 'Terminée', 'cancelled' => 'Annulée', 'refunded' => 'Remboursée'] as $val => $lbl)
                                <option value="{{ $val }}" {{ $order->status === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Transporteur</label>
                        <input type="text" name="tracking_carrier" value="{{ old('tracking_carrier', $order->tracking_carrier) }}"
                               placeholder="Colissimo, Mondial Relay..."
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">N° de suivi</label>
                        <input type="text" name="tracking_number" value="{{ old('tracking_number', $order->tracking_number) }}"
                               placeholder="Numero de suivi"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Lien de suivi</label>
                        <input type="url" name="tracking_url" value="{{ old('tracking_url', $order->tracking_url) }}"
                               placeholder="https://..."
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>

                    <button type="submit" class="w-full py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition">
                        Mettre a jour
                    </button>

                    @php
                        $trackingLink = $order->tracking_url ?: ($order->tracking_number ? 'https://www.laposte.fr/outils/suivre-vos-envois?code=' . $order->tracking_number : null);
                    @endphp
                    @if($trackingLink)
                            <a href="{{ $trackingLink }}" target="_blank"
                               class="flex items-center justify-center gap-2 w-full py-2 border border-brand-200 rounded-lg text-sm font-medium text-brand-700 hover:bg-brand-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                Suivre l'expédition
                            </a>
                    @endif
                </form>
            </div>

            {{-- Payment info --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Paiement</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Methode</span>
                        <span class="text-gray-700">{{ ucfirst($order->payment_method ?? '-') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Payé le</span>
                        <span class="text-gray-700">{{ $order->paid_at ? $order->paid_at->format('d/m/Y H:i') : 'Non payé' }}</span>
                    </div>
                    @if($order->stripe_payment_intent_id)
                        <div class="flex justify-between">
                            <span class="text-gray-500">Stripe PI</span>
                            <span class="text-xs text-gray-500 font-mono">{{ Str::limit($order->stripe_payment_intent_id, 20) }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quick actions --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Actions rapides</h3>
                <div class="space-y-2">
                    <a href="{{ route('admin.orders.invoice', $order) }}"
                       class="flex items-center gap-2 w-full py-2 px-3 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Telecharger la facture
                    </a>

                    @if(in_array($order->status, ['pending', 'cancelled']))
                        <form action="{{ route('admin.orders.destroy', $order) }}" method="POST"
                              onsubmit="return confirm('Supprimer cette commande ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="flex items-center gap-2 w-full py-2 px-3 border border-red-200 rounded-lg text-sm text-red-600 hover:bg-red-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Supprimer la commande
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Expédition Boxtal --}}
            @if(in_array($order->shipping_key, ['boxtal', 'boxtal_intl']) || str_contains(strtolower($order->shipping_method ?? ''), 'relais'))
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-4">Boxtal</h3>

                    @if($order->boxtal_shipping_order_id)
                        <div class="space-y-3 text-sm text-gray-600">
                            <p><span class="font-medium">ID expédition :</span> {{ $order->boxtal_shipping_order_id }}</p>

                            @if($order->boxtal_label_url)
                                <a href="{{ $order->boxtal_label_url }}" target="_blank"
                                   class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Voir / imprimer l'étiquette
                                </a>
                            @else
                                <a href="{{ route('admin.orders.label', $order) }}" target="_blank"
                                   class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Récupérer l'étiquette
                                </a>
                            @endif

                            <form method="POST" action="{{ route('admin.orders.reset-shipment', $order) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:underline" onclick="return confirm('Dissocier cette expédition Boxtal ? (ne l\'annule pas sur Boxtal)')">
                                    Dissocier l'expédition
                                </button>
                            </form>
                        </div>
                    @else
                        <form method="POST" action="{{ route('admin.orders.create-shipment', $order) }}">
                            @csrf
                            <p class="text-sm text-gray-600 mb-3">
                                @if($order->relay_network === 'MONR_NETWORK')
                                    Mondial Relay
                                @elseif($order->relay_network === 'CHRP_NETWORK')
                                    Chronopost
                                @else
                                    Point relais
                                @endif
                                @if($order->relay_point_code)
                                    <br><span class="text-xs">Relais : {{ $order->relay_point_code }}</span>
                                @endif
                            </p>

                            @php $pkg = config('shipping.boxtal.default_package'); @endphp
                            <div class="space-y-2 mb-3">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs text-gray-500">Poids (kg)</label>
                                        <input type="number" name="weight" step="0.01" value="{{ $pkg['weight'] }}" class="w-full rounded-lg border-gray-300 text-sm py-1.5 px-2" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500">Longueur (cm)</label>
                                        <input type="number" name="length" value="{{ $pkg['length'] }}" class="w-full rounded-lg border-gray-300 text-sm py-1.5 px-2" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500">Largeur (cm)</label>
                                        <input type="number" name="width" value="{{ $pkg['width'] }}" class="w-full rounded-lg border-gray-300 text-sm py-1.5 px-2" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500">Hauteur (cm)</label>
                                        <input type="number" name="height" value="{{ $pkg['height'] }}" class="w-full rounded-lg border-gray-300 text-sm py-1.5 px-2" required>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 transition" onclick="return confirm('Créer l\'expédition Boxtal pour cette commande ?')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                                Créer l'expédition Boxtal
                            </button>
                        </form>
                    @endif
                </div>
            @endif

            {{-- Timeline --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Chronologie</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex items-start gap-3">
                        <div class="w-2 h-2 rounded-full bg-gray-300 mt-1.5 shrink-0"></div>
                        <div>
                            <div class="text-gray-700">Commande créée</div>
                            <div class="text-xs text-gray-400">{{ $order->created_at->format('d/m/Y a H:i') }}</div>
                        </div>
                    </div>
                    @if($order->paid_at)
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 rounded-full bg-green-400 mt-1.5 shrink-0"></div>
                            <div>
                                <div class="text-gray-700">Paiement recu</div>
                                <div class="text-xs text-gray-400">{{ $order->paid_at->format('d/m/Y a H:i') }}</div>
                            </div>
                        </div>
                    @endif
                    @if($order->shipped_at)
                        <div class="flex items-start gap-3">
                            <div class="w-2 h-2 rounded-full bg-blue-400 mt-1.5 shrink-0"></div>
                            <div>
                                <div class="text-gray-700">Expédiée</div>
                                <div class="text-xs text-gray-400">{{ $order->shipped_at->format('d/m/Y a H:i') }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
