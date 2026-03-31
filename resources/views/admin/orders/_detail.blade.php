<div class="space-y-5">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">{{ $order->number }}</h2>
            <p class="text-xs text-gray-500">{{ $order->created_at->format('d/m/Y à H:i') }}</p>
        </div>
        <x-admin.badge :status="$order->status" />
    </div>

    {{-- Flash messages --}}
    <template x-if="panelFlash">
        <div class="rounded-lg border p-3 text-sm"
             :class="panelFlashType === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800'"
             x-text="panelFlash"></div>
    </template>

    {{-- Items --}}
    <div class="rounded-xl border border-gray-200 bg-white">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-800">Articles</h3>
            <span class="text-xs text-gray-500">{{ $order->items->count() }} article(s)</span>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($order->items as $item)
                <div class="px-4 py-3">
                    <div class="flex justify-between">
                        <div class="flex-1 min-w-0">
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
                        </div>
                        <div class="text-right shrink-0 ml-3">
                            <div class="text-sm font-medium text-gray-700">{{ number_format($item->total, 2, ',', ' ') }} &euro;</div>
                            <div class="text-xs text-gray-400">{{ $item->quantity }} × {{ number_format($item->unit_price, 2, ',', ' ') }} &euro;</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Totals --}}
        <div class="px-4 py-3 border-t border-gray-200 space-y-1.5 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">Sous-total</span>
                <span class="text-gray-700">{{ number_format($order->subtotal, 2, ',', ' ') }} &euro;</span>
            </div>
            @if($order->discount_total > 0)
                <div class="flex justify-between">
                    <span class="text-green-600">Remise{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</span>
                    <span class="text-green-600">-{{ number_format($order->discount_total, 2, ',', ' ') }} &euro;</span>
                </div>
            @endif
            <div class="flex justify-between">
                <span class="text-gray-500">Livraison ({{ $order->shipping_method ?? '-' }})</span>
                <span class="text-gray-700">{{ $order->shipping_total > 0 ? number_format($order->shipping_total, 2, ',', ' ') . ' €' : 'Gratuit' }}</span>
            </div>
            <div class="flex justify-between font-semibold pt-1.5 border-t border-gray-100">
                <span class="text-gray-800">Total</span>
                <span class="text-gray-800">{{ number_format($order->total, 2, ',', ' ') }} &euro;</span>
            </div>
        </div>
    </div>

    {{-- Addresses --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <h3 class="text-xs font-semibold text-gray-800 mb-2 uppercase tracking-wide">Facturation</h3>
            <div class="text-sm text-gray-600 space-y-0.5">
                <p class="font-medium">{{ $order->billing_first_name }} {{ $order->billing_last_name }}</p>
                <p>{{ $order->billing_address_1 }}</p>
                @if($order->billing_address_2)<p>{{ $order->billing_address_2 }}</p>@endif
                <p>{{ $order->billing_postcode }} {{ $order->billing_city }}</p>
                <p>{{ $order->billing_country }}</p>
                @if($order->billing_phone)<p class="pt-1">{{ $order->billing_phone }}</p>@endif
                <p class="pt-1 text-brand-600">{{ $order->billing_email }}</p>
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <h3 class="text-xs font-semibold text-gray-800 mb-2 uppercase tracking-wide">Livraison</h3>
            <div class="text-sm text-gray-600 space-y-0.5">
                @if($order->shipping_address_1)
                    <p class="font-medium">{{ $order->shipping_first_name }} {{ $order->shipping_last_name }}</p>
                    <p>{{ $order->shipping_address_1 }}</p>
                    @if($order->shipping_address_2)<p>{{ $order->shipping_address_2 }}</p>@endif
                    <p>{{ $order->shipping_postcode }} {{ $order->shipping_city }}</p>
                    <p>{{ $order->shipping_country }}</p>
                @else
                    <p class="text-gray-400 italic">Identique à la facturation</p>
                @endif
                @if($order->relay_point_code)
                    <p class="pt-1 text-brand-600 font-medium">Relais : {{ $order->relay_point_code }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Customer note --}}
    @if($order->customer_note)
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <h3 class="text-xs font-semibold text-gray-800 mb-1 uppercase tracking-wide">Note du client</h3>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $order->customer_note }}</p>
        </div>
    @endif

    {{-- Status & actions --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <h3 class="text-xs font-semibold text-gray-800 mb-3 uppercase tracking-wide">Statut & actions</h3>

        <form data-panel-form action="{{ route('admin.orders.update', $order) }}" method="POST" class="space-y-3">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Statut</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-brand-500 focus:border-brand-500">
                        @foreach(['pending' => 'Non réglée', 'processing' => 'En cours', 'shipped' => 'Expédiée', 'completed' => 'Terminée', 'cancelled' => 'Annulée', 'refunded' => 'Remboursée'] as $val => $lbl)
                            <option value="{{ $val }}" {{ $order->status === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Transporteur</label>
                    <input type="text" name="tracking_carrier" value="{{ $order->tracking_carrier }}"
                           placeholder="Colissimo..."
                           class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">N° de suivi</label>
                <input type="text" name="tracking_number" value="{{ $order->tracking_number }}"
                       placeholder="Numéro de suivi"
                       class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-brand-500 focus:border-brand-500">
            </div>

            <button type="submit" class="w-full py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition">
                Mettre à jour
            </button>
        </form>
    </div>

    {{-- Payment --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <h3 class="text-xs font-semibold text-gray-800 mb-2 uppercase tracking-wide">Paiement</h3>
        <div class="grid grid-cols-2 gap-y-1.5 text-sm">
            <span class="text-gray-500">Méthode</span>
            <span class="text-gray-700 text-right">{{ ucfirst($order->payment_method ?? '-') }}</span>
            <span class="text-gray-500">Payé le</span>
            <span class="text-gray-700 text-right">{{ $order->paid_at ? $order->paid_at->format('d/m/Y H:i') : 'Non payé' }}</span>
            @if($order->stripe_payment_intent_id)
                <span class="text-gray-500">Stripe PI</span>
                <span class="text-xs text-gray-500 font-mono text-right">{{ Str::limit($order->stripe_payment_intent_id, 20) }}</span>
            @endif
            @if($order->paypal_order_id)
                <span class="text-gray-500">PayPal</span>
                <span class="text-xs text-gray-500 font-mono text-right">{{ Str::limit($order->paypal_order_id, 20) }}</span>
            @endif
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <h3 class="text-xs font-semibold text-gray-800 mb-2 uppercase tracking-wide">Actions rapides</h3>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.orders.invoice', $order) }}"
               class="inline-flex items-center gap-1.5 py-1.5 px-3 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Facture
            </a>
            <a href="{{ route('admin.orders.show', $order) }}"
               class="inline-flex items-center gap-1.5 py-1.5 px-3 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                Page complète
            </a>
            @if(in_array($order->status, ['pending', 'cancelled']))
                <form data-panel-form action="{{ route('admin.orders.destroy', $order) }}" method="POST"
                      data-confirm="Supprimer cette commande ?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-1.5 py-1.5 px-3 border border-red-200 rounded-lg text-sm text-red-600 hover:bg-red-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Supprimer
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Boxtal --}}
    @if(in_array($order->shipping_key, ['boxtal', 'boxtal_intl']) || str_contains(strtolower($order->shipping_method ?? ''), 'relais'))
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <h3 class="text-xs font-semibold text-gray-800 mb-3 uppercase tracking-wide">Boxtal</h3>

            @if($order->boxtal_shipping_order_id)
                <div class="space-y-3 text-sm text-gray-600">
                    <p><span class="font-medium">ID :</span> {{ $order->boxtal_shipping_order_id }}</p>

                    @if($order->boxtal_label_url)
                        <a href="{{ $order->boxtal_label_url }}" target="_blank"
                           class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Étiquette
                        </a>
                    @else
                        <a href="{{ route('admin.orders.label', $order) }}"
                           class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 transition">
                            Récupérer l'étiquette
                        </a>
                    @endif

                    <form data-panel-form method="POST" action="{{ route('admin.orders.reset-shipment', $order) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:underline" data-confirm="Dissocier cette expédition Boxtal ?">
                            Dissocier l'expédition
                        </button>
                    </form>
                </div>
            @else
                <form data-panel-form method="POST" action="{{ route('admin.orders.create-shipment', $order) }}">
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
                            — {{ $order->relay_point_code }}
                        @endif
                    </p>

                    @php $pkg = config('shipping.boxtal.default_package'); @endphp
                    <div class="grid grid-cols-2 gap-2 mb-3">
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

                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 transition"
                            data-confirm="Créer l'expédition Boxtal ?">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                        Créer l'expédition
                    </button>
                </form>
            @endif
        </div>
    @endif

    {{-- Timeline --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <h3 class="text-xs font-semibold text-gray-800 mb-2 uppercase tracking-wide">Chronologie</h3>
        <div class="space-y-2 text-sm">
            <div class="flex items-center gap-2">
                <div class="w-1.5 h-1.5 rounded-full bg-gray-300 shrink-0"></div>
                <span class="text-gray-600">Créée le {{ $order->created_at->format('d/m/Y à H:i') }}</span>
            </div>
            @if($order->paid_at)
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-green-400 shrink-0"></div>
                    <span class="text-gray-600">Payée le {{ $order->paid_at->format('d/m/Y à H:i') }}</span>
                </div>
            @endif
            @if($order->shipped_at)
                <div class="flex items-center gap-2">
                    <div class="w-1.5 h-1.5 rounded-full bg-blue-400 shrink-0"></div>
                    <span class="text-gray-600">Expédiée le {{ $order->shipped_at->format('d/m/Y à H:i') }}</span>
                </div>
            @endif
        </div>
    </div>
</div>
