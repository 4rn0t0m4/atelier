<x-layouts.app title="Réexpédition de votre commande" :noindex="true">

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <h1 class="text-2xl font-semibold text-brand-900 mb-2" style="font-family: Georgia, serif;">
        Réexpédition de votre commande
    </h1>
    <p class="text-sm text-gray-500 mb-8">Commande n°{{ $order->number }}</p>

    @if($reshipment->isPaid())

        <div class="bg-green-50 border border-green-200 rounded-xl p-6 mb-8">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-green-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <div>
                    <h2 class="text-base font-semibold text-green-800">Réexpédition réglée</h2>
                    <p class="text-sm text-green-700 mt-1">
                        Merci ! Votre paiement de <strong>{{ $reshipment->formatted_amount }}</strong> a bien été reçu
                        le {{ $reshipment->paid_at->format('d/m/Y à H:i') }}.
                        Nous préparons l'envoi de votre colis vers :
                    </p>
                    <p class="text-sm text-green-800 mt-2 font-medium">{{ $reshipment->relay_point_name }}</p>
                    <p class="text-xs text-green-700">{{ $reshipment->relay_point_address }}</p>
                    <p class="text-sm text-green-700 mt-3">Vous recevrez un email avec le numéro de suivi dès que le colis sera expédié.</p>
                </div>
            </div>
        </div>

    @elseif($reshipment->isCancelled())

        <div class="bg-gray-50 border border-gray-200 rounded-xl p-6 mb-8">
            <h2 class="text-base font-semibold text-gray-800">Ce lien n'est plus valide</h2>
            <p class="text-sm text-gray-600 mt-1">
                Cette demande de réexpédition a été annulée ou remplacée. Si vous avez reçu un email plus récent,
                utilisez le lien qu'il contient. Sinon, contactez-nous à
                <a href="mailto:contact@atelier-aubin.fr" class="text-brand-700 underline">contact@atelier-aubin.fr</a>.
            </p>
        </div>

    @else

        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-8 text-sm text-amber-900">
            <p>
                Votre colis nous a été retourné car il n'a pas été retiré dans le délai prévu par le point relais.
                Pour le recevoir à nouveau, choisissez un nouveau point relais ci-dessous puis réglez les frais de
                réexpédition de <strong>{{ $reshipment->formatted_amount }}</strong>.
            </p>
        </div>

        <form action="{{ route('reshipment.store', $reshipment->token) }}" method="POST" data-turbo="false"
              x-data="{
                  country: '{{ $country }}',
                  paymentMethod: '{{ old('payment_method', 'stripe') }}',

                  relayPointCode: '{{ old('relay_point_code') }}',
                  relayPointName: '{{ old('relay_point_name') }}',
                  relayPointAddress: '{{ old('relay_point_address') }}',
                  relayPointNetwork: '{{ old('relay_network') }}',
                  relayPoints: [],
                  relayLoading: false,
                  relaySearched: false,

                  init() {
                      if (this.$refs.relayZip.value && this.$refs.relayCity.value) {
                          this.searchRelayPoints(this.$refs.relayZip.value, this.$refs.relayCity.value);
                      }
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
                              body: JSON.stringify({ zipCode: zipCode, city: city, country: this.country })
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
                  }
              }">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-8">
                    <section>
                        <h2 class="text-base font-semibold text-brand-900 mb-4 pb-2 border-b border-brand-100">
                            Choisissez votre nouveau point relais
                            @if($countryLabel)<span class="text-sm font-normal text-gray-500">({{ $countryLabel }})</span>@endif
                        </h2>
                        @error('relay_point_code')<p class="text-xs text-red-500 mb-3">{{ $message }}</p>@enderror

                        @include('checkout._relay-picker', ['relayZip' => $relayZip, 'relayCity' => $relayCity])
                    </section>
                </div>

                <aside>
                    <div class="bg-brand-50 rounded-xl p-6 sticky top-24">
                        <h2 class="text-base font-semibold text-brand-900 mb-4">Récapitulatif</h2>

                        <ul class="space-y-2 mb-4 text-sm text-gray-700">
                            @foreach($order->items as $item)
                                <li>{{ $item->product_name }} <span class="text-gray-400">× {{ $item->quantity }}</span></li>
                            @endforeach
                        </ul>

                        <div class="border-t border-brand-200 pt-4 space-y-2 text-sm">
                            <div class="flex justify-between text-gray-600">
                                <span>Réexpédition en point relais</span>
                                <span>{{ $reshipment->formatted_amount }}</span>
                            </div>
                            <div class="flex justify-between font-semibold text-brand-900 pt-2 border-t border-brand-200 text-base">
                                <span>Total à régler</span>
                                <span>{{ $reshipment->formatted_amount }}</span>
                            </div>
                        </div>

                        <div class="border-t border-brand-200 pt-4 mt-4">
                            <p class="text-sm font-semibold text-brand-900 mb-3">Moyen de paiement</p>
                            <div class="space-y-2">
                                <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition"
                                       :class="paymentMethod === 'stripe' ? 'border-brand-600 bg-brand-50 ring-1 ring-brand-600' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="radio" name="payment_method" value="stripe" x-model="paymentMethod" class="text-brand-600 focus:ring-brand-500">
                                    <span class="text-sm text-gray-700">Carte bancaire</span>
                                </label>
                                <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer transition"
                                       :class="paymentMethod === 'paypal' ? 'border-brand-600 bg-brand-50 ring-1 ring-brand-600' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="radio" name="payment_method" value="paypal" x-model="paymentMethod" class="text-brand-600 focus:ring-brand-500">
                                    <span class="text-sm text-gray-700">PayPal</span>
                                </label>
                            </div>
                            @error('payment_method')<p class="text-xs text-red-500 mt-2">{{ $message }}</p>@enderror
                        </div>

                        <button type="submit"
                                class="mt-6 w-full bg-brand-700 text-white py-3 px-6 rounded font-medium hover:bg-brand-800 transition text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="!relayPointCode">
                            Passer au règlement →
                        </button>
                        <p class="mt-2 text-xs text-gray-500 text-center" x-show="!relayPointCode">Sélectionnez un point relais pour continuer.</p>

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

    @endif

    <p class="mt-8 text-sm text-gray-500">
        Une question ? Écrivez-nous à
        <a href="mailto:contact@atelier-aubin.fr" class="text-brand-700 underline">contact@atelier-aubin.fr</a>.
    </p>
</div>

@include('checkout._relay-map')
</x-layouts.app>
