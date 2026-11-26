{{--
    Sélecteur de point relais Boxtal (recherche, liste, carte, point choisi + champs cachés).
    Attend un composant Alpine parent exposant : relayPoints, relayLoading, relaySearched,
    relayPointCode/Name/Address/Network, searchRelayPoints(zip, city), selectRelayPoint(point).
    Variables optionnelles : $relayZip, $relayCity (valeurs initiales des champs de recherche).
--}}
                    <div class="mb-4 flex gap-3">
                        <input type="text" x-ref="relayZip" placeholder="Code postal" aria-label="Code postal point relais"
                               value="{{ $relayZip ?? '' }}"
                               :value="$el.value || document.querySelector('[name=billing_postcode]')?.value || ''"
                               class="w-32 border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                        <input type="text" x-ref="relayCity" placeholder="Ville" aria-label="Ville point relais"
                               value="{{ $relayCity ?? '' }}"
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
