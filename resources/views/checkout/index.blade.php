<x-layouts.app title="Finaliser la commande" :noindex="true">
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <h1 class="text-2xl font-semibold text-brand-900 mb-8" style="font-family: Georgia, serif;">
        Finaliser la commande
    </h1>

    <form action="{{ route('checkout.store') }}" method="POST" data-turbo="false"
          x-data="{
              shippingSame: {{ old('shipping_same', $prefill['shipping_same'] ?? true) ? 'true' : 'false' }},
              shippingMethod: '{{ old('shipping_method', 'colissimo') }}',
              billingCountry: '{{ old('billing_country', $prefill['billing_country'] ?? 'FR') }}',
              shippingCountryField: '{{ old('shipping_country', $prefill['shipping_country'] ?? 'FR') }}',
              shippingPrices: @js(collect($shippingMethods)->mapWithKeys(fn($m, $k) => [$k => $m['price']])),
              shippingZones: @js($shippingZones),
              subtotal: {{ $subtotal }},
              discountAmount: {{ $discount['amount'] }},

              get effectiveCountry() {
                  return (!this.shippingSame && this.shippingCountryField) ? this.shippingCountryField : this.billingCountry;
              },
              get currentZone() {
                  for (let [zone, cfg] of Object.entries(this.shippingZones)) {
                      if ((cfg.countries || []).includes(this.effectiveCountry)) return zone;
                  }
                  return 'FR';
              },
              get allowedMethods() {
                  return this.shippingZones[this.currentZone]?.methods || ['colissimo'];
              },
              get freeShippingThreshold() {
                  return this.shippingZones[this.currentZone]?.free_shipping_threshold || 0;
              },
              get shippingCost() {
                  let price = this.shippingPrices[this.shippingMethod] ?? 0;
                  if (this.freeShippingThreshold && this.subtotal >= this.freeShippingThreshold) {
                      let method = this.shippingMethod;
                      // Only colissimo and boxtal support free_above_threshold
                      if (['boxtal'].includes(method)) price = 0;
                  }
                  return price;
              },
              get total() { return Math.max(0, this.subtotal - this.discountAmount + this.shippingCost) },
              formatPrice(v) { return v.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' \u20ac' },

              // Boxtal relay point
              relayPointCode: '{{ old('relay_point_code') }}',
              relayPointName: '{{ old('relay_point_name') }}',
              relayPointAddress: '{{ old('relay_point_address') }}',
              relayPointNetwork: '{{ old('relay_network') }}',
              relayPoints: [],
              relayLoading: false,
              relaySearched: false,

              get isRelayMethod() {
                  return ['boxtal', 'boxtal_intl'].includes(this.shippingMethod);
              },

              async searchRelayPoints(zipCode, city) {
                  if (!zipCode || !city) return;
                  this.relayLoading = true;
                  this.relaySearched = false;
                  try {
                      const res = await fetch('{{ route('boxtal.parcel-points') }}', {
                          method: 'POST',
                          headers: {
                              'Content-Type': 'application/json',
                              'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                          },
                          body: JSON.stringify({ zipCode: zipCode, city: city, country: this.effectiveCountry })
                      });
                      const data = await res.json();
                      this.relayPoints = data.points || [];
                  } catch (e) {
                      this.relayPoints = [];
                  }
                  this.relayLoading = false;
                  this.relaySearched = true;
                  if (this.relayPoints.length > 0) {
                      this.$nextTick(() => window.__relayMap.render(this));
                  }
              },

              selectRelayPoint(point) {
                  this.relayPointCode = point.code;
                  this.relayPointName = point.name;
                  this.relayPointAddress = [point.street, point.zipCode, point.city].filter(Boolean).join(', ');
                  this.relayPointNetwork = point.network;
                  window.__relayMap.highlight(point);
              },

              resetRelay() {
                  this.relayPoints = [];
                  this.relayPointCode = '';
                  this.relayPointName = '';
                  this.relayPointAddress = '';
                  this.relayPointNetwork = '';
                  this.relaySearched = false;
                  window.__relayMap.destroy();
              },

              autoSearchRelay() {
                  let zip, city;
                  if (!this.shippingSame) {
                      zip = document.querySelector('[name=shipping_postcode]')?.value;
                      city = document.querySelector('[name=shipping_city]')?.value;
                  }
                  if (!zip || !city) {
                      zip = document.querySelector('[name=billing_postcode]')?.value;
                      city = document.querySelector('[name=billing_city]')?.value;
                  }
                  if (zip && city) {
                      this.$refs.relayZip.value = zip;
                      this.$refs.relayCity.value = city;
                      this.searchRelayPoints(zip, city);
                  }
              },

              onCountryChange() {
                  if (!this.allowedMethods.includes(this.shippingMethod)) {
                      this.shippingMethod = this.allowedMethods[0] || 'colissimo';
                  }
                  this.resetRelay();
              }
          }"
          x-effect="if (isRelayMethod && !relaySearched && !relayLoading) { $nextTick(() => autoSearchRelay()) } else if (!isRelayMethod) { resetRelay() }">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- Colonne gauche : formulaire --}}
            <div class="lg:col-span-2 space-y-8">

                {{-- Adresse de facturation --}}
                <section>
                    <h2 class="text-base font-semibold text-brand-900 mb-4 pb-2 border-b border-brand-100">
                        Adresse de facturation
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Prénom *</label>
                            <input type="text" name="billing_first_name"
                                   value="{{ old('billing_first_name', $prefill['billing_first_name'] ?? '') }}"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500 @error('billing_first_name') border-red-400 @enderror"
                                   required>
                            @error('billing_first_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Nom *</label>
                            <input type="text" name="billing_last_name"
                                   value="{{ old('billing_last_name', $prefill['billing_last_name'] ?? '') }}"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500 @error('billing_last_name') border-red-400 @enderror"
                                   required>
                            @error('billing_last_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">E-mail *</label>
                            <input type="email" name="billing_email"
                                   value="{{ old('billing_email', $prefill['billing_email'] ?? auth()->user()?->email) }}"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500 @error('billing_email') border-red-400 @enderror"
                                   required>
                            @error('billing_email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Téléphone</label>
                            <input type="tel" name="billing_phone"
                                   value="{{ old('billing_phone', $prefill['billing_phone'] ?? '') }}"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm text-gray-700 mb-1">Adresse *</label>
                            <input type="text" name="billing_address_1"
                                   value="{{ old('billing_address_1', $prefill['billing_address_1'] ?? '') }}"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500 @error('billing_address_1') border-red-400 @enderror"
                                   required>
                            @error('billing_address_1')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm text-gray-700 mb-1">Complément d'adresse</label>
                            <input type="text" name="billing_address_2"
                                   value="{{ old('billing_address_2', $prefill['billing_address_2'] ?? '') }}"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Code postal *</label>
                            <input type="text" name="billing_postcode"
                                   value="{{ old('billing_postcode', $prefill['billing_postcode'] ?? '') }}"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500 @error('billing_postcode') border-red-400 @enderror"
                                   required>
                            @error('billing_postcode')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Ville *</label>
                            <input type="text" name="billing_city"
                                   value="{{ old('billing_city', $prefill['billing_city'] ?? '') }}"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500 @error('billing_city') border-red-400 @enderror"
                                   required>
                            @error('billing_city')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Pays *</label>
                            <select name="billing_country"
                                    x-model="billingCountry"
                                    @change="if (shippingSame) onCountryChange()"
                                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500"
                                    required>
                                @foreach($shippingCountries as $code => $label)
                                    <option value="{{ $code }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                {{-- Adresse de livraison --}}
                <section>
                    <div class="flex items-center justify-between mb-4 pb-2 border-b border-brand-100">
                        <h2 class="text-base font-semibold text-brand-900">Adresse de livraison</h2>
                        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer"
                               x-show="!isRelayMethod">
                            <input type="checkbox" name="shipping_same" value="1"
                                   x-model="shippingSame"
                                   class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            Identique à la facturation
                        </label>
                    </div>

                    <div x-show="!shippingSame && !isRelayMethod" x-transition class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Prénom</label>
                            <input type="text" name="shipping_first_name"
                                   value="{{ old('shipping_first_name', $prefill['shipping_first_name'] ?? '') }}"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Nom</label>
                            <input type="text" name="shipping_last_name"
                                   value="{{ old('shipping_last_name', $prefill['shipping_last_name'] ?? '') }}"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm text-gray-700 mb-1">Adresse</label>
                            <input type="text" name="shipping_address_1"
                                   value="{{ old('shipping_address_1', $prefill['shipping_address_1'] ?? '') }}"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm text-gray-700 mb-1">Complément</label>
                            <input type="text" name="shipping_address_2"
                                   value="{{ old('shipping_address_2', $prefill['shipping_address_2'] ?? '') }}"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Code postal</label>
                            <input type="text" name="shipping_postcode"
                                   value="{{ old('shipping_postcode', $prefill['shipping_postcode'] ?? '') }}"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Ville</label>
                            <input type="text" name="shipping_city"
                                   value="{{ old('shipping_city', $prefill['shipping_city'] ?? '') }}"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Pays</label>
                            <select name="shipping_country"
                                    x-model="shippingCountryField"
                                    @change="onCountryChange()"
                                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                                @foreach($shippingCountries as $code => $label)
                                    <option value="{{ $code }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </section>

                {{-- Mode de livraison --}}
                <section>
                    <h2 class="text-base font-semibold text-brand-900 mb-4 pb-2 border-b border-brand-100">
                        Mode de livraison
                    </h2>
                    @error('shipping_method')<p class="text-xs text-red-500 mb-3">{{ $message }}</p>@enderror

                    <div x-show="currentZone !== 'FR'" x-transition
                         class="mb-3 p-3 text-sm rounded-lg bg-brand-50 border border-brand-200 text-brand-800">
                        Les modes de livraison disponibles ont été mis à jour en fonction du pays sélectionné.
                    </div>

                    <div class="space-y-3">
                        @foreach($shippingMethods as $key => $method)
                            <label class="flex items-center justify-between p-4 border rounded-lg cursor-pointer transition"
                                   x-show="allowedMethods.includes('{{ $key }}')"
                                   x-transition
                                   :class="shippingMethod === '{{ $key }}'
                                       ? 'border-brand-600 bg-brand-50 ring-1 ring-brand-600'
                                       : 'border-gray-200 hover:border-gray-300'">
                                <div class="flex items-center gap-3">
                                    <input type="radio" name="shipping_method" value="{{ $key }}"
                                           x-model="shippingMethod"
                                           class="text-brand-600 focus:ring-brand-500"
                                           {{ old('shipping_method', 'colissimo') === $key ? 'checked' : '' }}>
                                    <span class="text-sm font-medium text-gray-900">{{ $method['label'] }}</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-700">
                                    @if(!empty($method['free_above_threshold']))
                                        <template x-if="freeShippingThreshold && subtotal >= freeShippingThreshold">
                                            <span class="text-brand-700">Gratuit</span>
                                        </template>
                                        <template x-if="!freeShippingThreshold || subtotal < freeShippingThreshold">
                                            <span>{{ number_format($method['price'], 2, ',', ' ') }} €</span>
                                        </template>
                                    @else
                                        {{ $method['price'] > 0 ? number_format($method['price'], 2, ',', ' ') . ' €' : 'Gratuit' }}
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </section>

                {{-- Points relais Boxtal --}}
                <section x-show="isRelayMethod" x-transition>
                    <h2 class="text-base font-semibold text-brand-900 mb-4 pb-2 border-b border-brand-100">
                        Choisissez votre point relais
                    </h2>
                    @error('relay_point_code')<p class="text-xs text-red-500 mb-3">{{ $message }}</p>@enderror

                    <div class="mb-4 flex gap-3">
                        <input type="text" x-ref="relayZip" placeholder="Code postal"
                               :value="$el.value || document.querySelector('[name=billing_postcode]')?.value || ''"
                               class="w-32 border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                        <input type="text" x-ref="relayCity" placeholder="Ville"
                               :value="$el.value || document.querySelector('[name=billing_city]')?.value || ''"
                               class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                        <button type="button"
                                @click="searchRelayPoints($refs.relayZip.value, $refs.relayCity.value)"
                                :disabled="relayLoading"
                                class="bg-brand-700 text-white px-4 py-2 rounded text-sm font-medium hover:bg-brand-800 transition disabled:opacity-50">
                            <span x-show="!relayLoading">Rechercher</span>
                            <span x-show="relayLoading" class="flex items-center gap-1">
                                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                Recherche…
                            </span>
                        </button>
                    </div>

                    {{-- Carte + Liste --}}
                    <div x-show="relayPoints.length > 0" x-transition class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg divide-y divide-gray-100 order-2 sm:order-1">
                            <template x-for="(point, idx) in relayPoints" :key="point.code">
                                <div class="flex items-start gap-3 p-3 cursor-pointer transition border-l-4"
                                     @click="selectRelayPoint(point)"
                                     :class="relayPointCode === point.code
                                         ? (point.network === 'CHRP_NETWORK' ? 'bg-blue-50 border-l-[#337ab7]' : 'bg-pink-50 border-l-[#96154a]')
                                         : 'hover:bg-gray-50 border-l-transparent'"
                                     :id="'relay-item-' + point.code">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-white text-xs font-bold shrink-0"
                                                  :class="point.network === 'CHRP_NETWORK' ? 'bg-[#337ab7]' : 'bg-[#96154a]'"
                                                  x-text="idx + 1"></span>
                                            <span class="text-sm font-medium text-gray-900" x-text="point.name"></span>
                                        </div>
                                        <p class="text-xs text-gray-500 ml-7" x-text="[point.street, point.zipCode + ' ' + point.city].filter(Boolean).join(', ')"></p>
                                        <div class="ml-7 mt-1" x-show="relayPointCode === point.code && point.openingDays && point.openingDays.length > 0" x-transition>
                                            <table class="text-[11px] text-gray-500 leading-tight">
                                                <template x-for="d in (point.openingDays || [])" :key="d.day">
                                                    <tr>
                                                        <td class="pr-2 font-medium text-gray-600" x-text="d.day"></td>
                                                        <td x-text="d.slots || 'Fermé'"></td>
                                                    </tr>
                                                </template>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="order-1 sm:order-2">
                            <div id="relay-map" class="w-full rounded-lg border border-gray-200 overflow-hidden" style="height: 384px;"></div>
                        </div>
                    </div>

                    {{-- Légende réseaux --}}
                    <div x-show="relayPoints.length > 0" class="flex items-center gap-4 text-xs text-gray-500 mt-3">
                        <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-full" style="background:#337ab7"></span> Chronopost</span>
                        <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-full" style="background:#96154a"></span> Mondial Relay</span>
                    </div>

                    {{-- Aucun résultat --}}
                    <div x-show="relaySearched && relayPoints.length === 0 && !relayLoading" x-transition
                         class="p-4 text-sm text-gray-500 text-center border border-gray-200 rounded-lg">
                        Aucun point relais trouvé pour cette recherche. Essayez avec une autre ville ou code postal.
                    </div>

                    {{-- Point sélectionné --}}
                    <div x-show="relayPointName" x-transition
                         class="mt-4 p-4 bg-brand-50 border border-brand-200 rounded-lg">
                        <p class="text-sm text-brand-800">
                            <span class="font-semibold">Point relais sélectionné :</span>
                            <span x-text="relayPointName"></span>
                        </p>
                        <p class="text-xs text-brand-700 mt-1" x-text="relayPointAddress"></p>
                    </div>

                    <input type="hidden" name="relay_point_code" :value="relayPointCode">
                    <input type="hidden" name="relay_point_name" :value="relayPointName">
                    <input type="hidden" name="relay_point_address" :value="relayPointAddress">
                    <input type="hidden" name="relay_network" :value="relayPointNetwork">
                </section>

                {{-- Note de commande --}}
                <section>
                    <h2 class="text-base font-semibold text-brand-900 mb-4 pb-2 border-b border-brand-100">
                        Note de commande (optionnel)
                    </h2>
                    <textarea name="customer_note" rows="3"
                              placeholder="Instructions particulières pour votre commande..."
                              class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('customer_note') }}</textarea>
                </section>
            </div>

            {{-- Colonne droite : récapitulatif --}}
            <aside>
                <div class="bg-brand-50 rounded-xl p-6 sticky top-24">
                    <h2 class="text-base font-semibold text-brand-900 mb-4">Récapitulatif</h2>

                    <ul class="space-y-3 mb-4">
                        @foreach($items as $item)
                            <li class="text-sm text-gray-700">
                                <div class="flex justify-between">
                                    <span>
                                        {{ $item['name'] }}
                                        <span class="text-gray-400">× {{ $item['quantity'] }}</span>
                                    </span>
                                    <span>
                                        @php
                                            $addonPerUnit = $item['addon_price_per_unit'] ?? 0;
                                            $addonFlat = $item['addon_price_flat'] ?? 0;
                                            $lineTotal = ($item['price'] + $addonPerUnit) * $item['quantity'] + $addonFlat;
                                        @endphp
                                        {{ number_format($lineTotal, 2, ',', ' ') }} €
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    {{-- Code promo --}}
                    <div class="mb-4" x-data="{ couponOpen: {{ old('coupon_code') ? 'true' : 'false' }} }">
                        <button type="button" @click="couponOpen = !couponOpen"
                                class="text-sm text-brand-700 hover:text-brand-800 font-medium flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            Code promo
                        </button>
                        <div x-show="couponOpen" x-transition class="mt-2 flex gap-2">
                            <input type="text" name="coupon_code"
                                   value="{{ old('coupon_code') }}"
                                   placeholder="Entrez votre code"
                                   class="flex-1 border border-gray-300 rounded px-3 py-1.5 text-sm focus:ring-brand-500 focus:border-brand-500 uppercase">
                        </div>
                        @error('coupon_code')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="border-t border-brand-200 pt-4 space-y-2 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Sous-total</span>
                            <span>{{ number_format($subtotal, 2, ',', ' ') }} €</span>
                        </div>

                        @if($discount['amount'] > 0)
                            <div class="flex justify-between text-green-700">
                                <span>Remise{{ $discount['label'] ? ' ('.$discount['label'].')' : '' }}</span>
                                <span>−{{ number_format($discount['amount'], 2, ',', ' ') }} €</span>
                            </div>
                        @endif

                        <div class="flex justify-between text-gray-600">
                            <span>Livraison</span>
                            <span x-text="shippingCost > 0 ? formatPrice(shippingCost) : 'Gratuit'"></span>
                        </div>

                        <div class="flex justify-between font-semibold text-brand-900 pt-2 border-t border-brand-200 text-base">
                            <span>Total</span>
                            <span x-text="formatPrice(total)"></span>
                        </div>
                    </div>

                    {{-- Moyen de paiement --}}
                    <div class="border-t border-brand-200 pt-4 mt-4">
                        <p class="text-sm font-semibold text-brand-900 mb-3">Moyen de paiement</p>
                        <div class="space-y-2" x-data="{ paymentMethod: '{{ old('payment_method', 'stripe') }}' }">
                            <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition"
                                   :class="paymentMethod === 'stripe' ? 'border-brand-600 bg-brand-50 ring-1 ring-brand-600' : 'border-gray-200 hover:border-gray-300'">
                                <input type="radio" name="payment_method" value="stripe"
                                       x-model="paymentMethod"
                                       class="text-brand-600 focus:ring-brand-500">
                                <svg class="h-5 w-auto" viewBox="0 0 60 25" fill="none"><rect width="60" height="25" rx="4" fill="#635BFF"/><path d="M28.3 10.4c0-1.7 1.4-2.4 2.5-2.4 1 0 1.9.4 2.6 1.1l1.2-1.4c-.9-.9-2.2-1.4-3.7-1.4-2.5 0-4.5 1.5-4.5 4s2 4 4.5 4c1.5 0 2.8-.5 3.7-1.4l-1.2-1.4c-.7.7-1.6 1.1-2.6 1.1-1.1 0-2.5-.7-2.5-2.4zm-7.8-4.1h-1.8v8h1.8v-3.1c0-1.6 1-2.3 1.8-2.3.2 0 .5 0 .8.1l.3-1.8c-.3-.1-.6-.1-.9-.1-1 0-1.7.5-2 1.1V6.3zm-6 8h1.8v-8h-1.8v8zm0-9.4h1.8V3h-1.8v1.9zm-3.3 5.6c0-1.3 1-2.2 2.2-2.2s2.2.9 2.2 2.2-1 2.2-2.2 2.2-2.2-.9-2.2-2.2zm-1.8 0c0 2.3 1.7 4 4 4s4-1.7 4-4-1.7-4-4-4-4 1.7-4 4zm25.8-4h-1.8v8h1.8v-3.1c0-1.6 1-2.3 1.8-2.3.2 0 .5 0 .8.1l.3-1.8c-.3-.1-.6-.1-.9-.1-1 0-1.7.5-2 1.1V6.3zm6.8 4c0-1.3.9-2.2 2-2.2 1 0 2 .9 2 2.2s-1 2.2-2 2.2c-1.1 0-2-.9-2-2.2zm5.8-4H46v.8c-.6-.7-1.5-1-2.5-1-2.1 0-3.7 1.7-3.7 4s1.6 4 3.7 4c1 0 1.9-.4 2.5-1v.8h1.8v-8z" fill="#fff"/></svg>
                                <span class="text-sm text-gray-700">Carte bancaire</span>
                            </label>
                            <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition"
                                   :class="paymentMethod === 'paypal' ? 'border-brand-600 bg-brand-50 ring-1 ring-brand-600' : 'border-gray-200 hover:border-gray-300'">
                                <input type="radio" name="payment_method" value="paypal"
                                       x-model="paymentMethod"
                                       class="text-brand-600 focus:ring-brand-500">
                                <svg class="h-5 w-auto" viewBox="0 0 101 32" fill="none"><path d="M12.2 4.3H4.9c-.5 0-.9.4-1 .8L1 27.5c0 .4.2.7.6.7h3.5c.5 0 .9-.4 1-.8l.8-5.4c0-.5.5-.9 1-.9h2.2c4.7 0 7.4-2.3 8.1-6.8.3-2 0-3.5-.9-4.6C16.2 5.4 14.5 4.3 12.2 4.3zm.8 6.7c-.4 2.6-2.3 2.6-4.2 2.6h-1l.8-4.8c0-.3.3-.5.6-.5h.5c1.3 0 2.5 0 3.1.7.4.5.5 1.1.3 2zM35.5 10.9h-3.5c-.3 0-.5.2-.6.5l-.2 1-.3-.4c-.8-1.2-2.7-1.6-4.5-1.6-4.2 0-7.8 3.2-8.5 7.7-.4 2.2.2 4.4 1.4 5.9 1.2 1.3 2.8 1.9 4.8 1.9 3.4 0 5.3-2.2 5.3-2.2l-.2 1c0 .4.2.7.6.7h3.2c.5 0 .9-.4 1-.8l1.9-12c0-.4-.3-.7-.6-.7zm-5.3 7.5c-.4 2.2-2 3.6-4.2 3.6-1.1 0-2-.4-2.6-1-.6-.7-.8-1.6-.6-2.7.3-2.2 2.1-3.7 4.2-3.7 1.1 0 2 .4 2.6 1 .6.7.8 1.7.6 2.7zM55.3 10.9h-3.5c-.3 0-.6.2-.8.4l-4.5 6.6-1.9-6.4c-.1-.4-.5-.6-1-.6h-3.4c-.4 0-.7.4-.6.8L43.5 24l-3.7 5.2c-.3.4 0 1 .5 1h3.5c.3 0 .6-.2.8-.4l10.9-15.7c.3-.5 0-1-.5-1z" fill="#253B80"/><path d="M67.2 4.3h-7.3c-.5 0-.9.4-1 .8L56 27.5c0 .4.2.7.6.7h3.7c.3 0 .6-.3.7-.6l.8-5.5c0-.5.5-.9 1-.9h2.2c4.7 0 7.4-2.3 8.1-6.8.3-2 0-3.5-.9-4.6-1.1-1.3-2.8-2.5-5.1-2.5zm.8 6.7c-.4 2.6-2.3 2.6-4.2 2.6h-1l.8-4.8c0-.3.3-.5.6-.5h.5c1.3 0 2.5 0 3.1.7.3.5.4 1.1.2 2zM90.4 10.9h-3.5c-.3 0-.5.2-.6.5l-.2 1-.2-.4c-.8-1.2-2.7-1.6-4.5-1.6-4.2 0-7.8 3.2-8.5 7.7-.4 2.2.2 4.4 1.4 5.9 1.2 1.3 2.8 1.9 4.8 1.9 3.4 0 5.3-2.2 5.3-2.2l-.2 1c0 .4.2.7.6.7h3.2c.5 0 .9-.4 1-.8l1.9-12c0-.4-.3-.7-.6-.7zm-5.3 7.5c-.4 2.2-2 3.6-4.2 3.6-1.1 0-2-.4-2.6-1-.6-.7-.8-1.6-.6-2.7.3-2.2 2.1-3.7 4.2-3.7 1.1 0 2 .4 2.6 1 .6.7.8 1.7.6 2.7zM93.4 4.8l-3 19.1c0 .4.2.7.6.7h3c.5 0 .9-.4 1-.8l3-18.5c0-.4-.2-.7-.6-.7h-3.4c-.3 0-.5.2-.6.5z" fill="#179BD7"/></svg>
                                <span class="text-sm text-gray-700">PayPal</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit"
                            class="mt-6 w-full bg-brand-700 text-white py-3 px-6 rounded font-medium hover:bg-brand-800 transition text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="isRelayMethod && !relayPointCode">
                        Passer au règlement →
                    </button>

                    <p class="mt-3 text-xs text-gray-400 text-center flex items-center justify-center gap-1">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Paiement sécurisé
                    </p>
                </div>
            </aside>
        </div>
    </form>
</div>

<script>
window.__relayMap = (function() {
    var map = null;
    var markers = [];

    function loadMapLibre(cb) {
        if (window.maplibregl) { cb(); return; }
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css';
        document.head.appendChild(link);
        var script = document.createElement('script');
        script.src = 'https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js';
        script.onload = cb;
        document.head.appendChild(script);
    }

    function initMap(alpine) {
        var container = document.getElementById('relay-map');
        if (!container) return;
        if (map) { map.remove(); map = null; }
        markers = [];

        var points = alpine.relayPoints;
        var bounds = new maplibregl.LngLatBounds();
        points.forEach(function(p) {
            if (p.lat && p.lng) bounds.extend([p.lng, p.lat]);
        });

        map = new maplibregl.Map({
            container: container,
            style: 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json',
            bounds: bounds,
            fitBoundsOptions: { padding: 40, maxZoom: 14 }
        });
        map.addControl(new maplibregl.NavigationControl(), 'top-right');

        points.forEach(function(point, idx) {
            if (!point.lat || !point.lng) return;

            var isChrono = point.network === 'CHRP_NETWORK';
            var el = document.createElement('div');
            el.className = 'relay-marker' + (isChrono ? ' relay-marker--chrono' : '');
            el.textContent = idx + 1;
            el.addEventListener('click', function() {
                alpine.selectRelayPoint(point);
                var item = document.getElementById('relay-item-' + point.code);
                if (item) item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });

            var popupHtml = '<strong class="relay-popup-name">' +
                point.name + '</strong><br><span class="relay-popup-addr">' +
                point.street + '<br>' + point.zipCode + ' ' + point.city + '</span>';

            var marker = new maplibregl.Marker({ element: el })
                .setLngLat([point.lng, point.lat])
                .setPopup(new maplibregl.Popup({ offset: 20 }).setHTML(popupHtml))
                .addTo(map);

            markers.push({ code: point.code, el: el, marker: marker });
        });
    }

    return {
        render: function(alpine) {
            loadMapLibre(function() { initMap(alpine); });
        },
        highlight: function(point) {
            if (map && point.lat && point.lng) {
                map.flyTo({ center: [point.lng, point.lat], zoom: 15 });
            }
            markers.forEach(function(m) {
                m.el.style.opacity = m.code === point.code ? '1' : '0.5';
                m.el.style.transform = m.code === point.code ? 'scale(1.3)' : 'scale(1)';
            });
        },
        destroy: function() {
            if (map) { map.remove(); map = null; }
            markers = [];
        }
    };
})();
</script>
<style>
.relay-marker {
    width: 28px; height: 28px; border-radius: 50%;
    background: #96154a; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; cursor: pointer;
    border: 2px solid #fff;
    box-shadow: 0 2px 6px rgba(0,0,0,.3);
    transition: transform .2s, opacity .2s;
}
.relay-marker--chrono { background: #337ab7; }
.relay-popup-name { font-size: 13px; display: block; }
.relay-popup-addr { font-size: 12px; color: #666; }
.maplibregl-popup-close-button { font-size: 20px; width: 28px; height: 28px; line-height: 28px; padding: 0; }
</style>
</x-layouts.app>
