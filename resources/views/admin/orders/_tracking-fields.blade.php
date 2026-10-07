@php
    /**
     * Champs transporteur / suivi, partages par la fiche commande, le panneau
     * lateral et le formulaire d'edition.
     *
     * @var \App\Models\Order $order
     */
    $labelClass = $labelClass ?? 'block text-xs text-gray-500 mb-1';
    $inputClass = $inputClass ?? 'w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500';
    $showUrl = $showUrl ?? true;

    $carrierValue = trim((string) old('tracking_carrier', $order->tracking_carrier));
    $knownCarrier = collect(\App\Models\Order::LA_POSTE_CARRIERS)
        ->first(fn ($c) => mb_strtolower($c) === mb_strtolower($carrierValue));
    $carrierChoice = $carrierValue === '' ? '' : ($knownCarrier ?: 'Autre');
    $customCarrier = $knownCarrier ? '' : $carrierValue;
@endphp

<div class="space-y-4"
     x-data="{
        choice: @js($carrierChoice),
        custom: @js($customCarrier),
        number: @js((string) \App\Models\Order::normalizeTrackingNumber(old('tracking_number', $order->tracking_number))),
        url: @js((string) old('tracking_url', $order->tracking_url)),
        get autoUrl() { return this.choice === 'Colissimo' || this.choice === 'La Poste'; },
     }"
     x-effect="if (autoUrl) url = number ? 'https://www.laposte.fr/outils/suivre-vos-envois?code=' + encodeURIComponent(number) : ''">

    <div>
        <label class="{{ $labelClass }}">Transporteur</label>
        <input type="hidden" name="tracking_carrier" :value="choice === 'Autre' ? custom : choice">
        <select x-model="choice" class="{{ $inputClass }}">
            <option value="">-- Aucun --</option>
            @foreach(\App\Models\Order::LA_POSTE_CARRIERS as $carrier)
                <option value="{{ $carrier }}">{{ $carrier }}</option>
            @endforeach
            <option value="Autre">Autre</option>
        </select>
        <input type="text" x-model="custom" x-show="choice === 'Autre'" x-cloak
               placeholder="Nom du transporteur"
               class="mt-2 {{ $inputClass }}">
    </div>

    <div>
        <label class="{{ $labelClass }}">N&deg; de suivi</label>
        {{-- Les numeros copies-colles contiennent souvent des espaces --}}
        <input type="text" name="tracking_number" x-model="number"
               @input="number = number.replace(/\s+/g, '')"
               placeholder="Numero de suivi" class="{{ $inputClass }}">
    </div>

    @if($showUrl)
        <div>
            <label class="{{ $labelClass }}">Lien de suivi</label>
            <input type="url" name="tracking_url" x-model="url" :readonly="autoUrl"
                   placeholder="https://..." class="{{ $inputClass }}"
                   :class="autoUrl ? 'bg-gray-50 text-gray-500' : ''">
            <p x-show="autoUrl" x-cloak class="mt-1 text-xs text-gray-400">
                Genere automatiquement depuis le numero de suivi La Poste.
            </p>
        </div>
    @endif
</div>
