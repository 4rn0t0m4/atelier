@php
    /**
     * Bloc « Réexpédition en relais » : historique des demandes + création d'un nouveau lien.
     *
     * @var \App\Models\Order $order
     */
    $reshipments = $order->reshipments;
    $pending = $order->pendingReshipment();
    $defaultAmount = app(\App\Services\ReshipmentService::class)->defaultAmount($order);
    $inputClass = 'w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-brand-500 focus:border-brand-500';
@endphp

<div class="rounded-2xl border border-gray-200 bg-white p-5" x-data="{ open: {{ $errors->hasAny(['amount', 'email']) ? 'true' : 'false' }} }">
    <h3 class="text-sm font-semibold text-gray-800 mb-1">Réexpédition en relais</h3>
    <p class="text-xs text-gray-500 mb-4">Colis retourné (non retiré) : le client choisit un nouveau relais et règle les frais via un lien.</p>

    @foreach($reshipments as $reshipment)
        <div class="mb-3 p-3 rounded-lg border text-sm {{ $reshipment->isPaid() ? 'border-green-200 bg-green-50' : ($reshipment->isPending() ? 'border-amber-200 bg-amber-50' : 'border-gray-200 bg-gray-50') }}">
            <div class="flex items-center justify-between gap-2">
                <span class="font-medium text-gray-800">{{ $reshipment->formatted_amount }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full {{ $reshipment->isPaid() ? 'bg-green-100 text-green-700' : ($reshipment->isPending() ? 'bg-amber-100 text-amber-700' : 'bg-gray-200 text-gray-600') }}">
                    {{ $reshipment->status_label }}
                </span>
            </div>
            <p class="text-xs text-gray-500 mt-1">
                Créée le {{ $reshipment->created_at->format('d/m/Y à H:i') }}
                @if($reshipment->email_sent_at) · email envoyé le {{ $reshipment->email_sent_at->format('d/m/Y à H:i') }} @endif
            </p>

            @if($reshipment->isPaid())
                <p class="text-xs text-gray-700 mt-2">
                    Payée le {{ $reshipment->paid_at->format('d/m/Y à H:i') }}
                    ({{ $reshipment->payment_method === 'paypal' ? 'PayPal' : 'CB' }})
                    <br><span class="font-medium">Relais :</span> {{ $reshipment->relay_point_name }}
                    @if($reshipment->relay_network_label) · {{ $reshipment->relay_network_label }} @endif
                    <br><span class="text-gray-500">{{ $reshipment->relay_point_address }} · code {{ $reshipment->relay_point_code }}</span>
                </p>
                @if($reshipment->previous_tracking_number)
                    <p class="text-xs text-gray-400 mt-1">Premier envoi : suivi {{ $reshipment->previous_tracking_number }}</p>
                @endif
            @elseif($reshipment->isPending())
                <div class="mt-2" x-data="{ copied: false }">
                    <label class="block text-xs text-gray-500 mb-1">Lien à transmettre au client</label>
                    <div class="flex gap-1">
                        <input type="text" readonly value="{{ $reshipment->url }}" x-ref="link"
                               class="flex-1 min-w-0 border border-gray-300 rounded-lg px-2 py-1 text-xs text-gray-600 bg-white">
                        <button type="button"
                                @click="navigator.clipboard.writeText($refs.link.value).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                class="px-2 py-1 border border-gray-300 rounded-lg text-xs text-gray-700 bg-white hover:bg-gray-50">
                            <span x-show="!copied">Copier</span><span x-show="copied" x-cloak>Copié !</span>
                        </button>
                    </div>
                </div>
                <div class="flex gap-3 mt-2">
                    <form action="{{ route('admin.orders.reshipments.resend', [$order, $reshipment]) }}" method="POST" onsubmit="return confirm('Renvoyer le lien de réexpédition à {{ $order->billing_email }} ?')">
                        @csrf
                        <button type="submit" class="text-xs text-brand-700 hover:underline">Renvoyer l'email</button>
                    </form>
                    <form action="{{ route('admin.orders.reshipments.cancel', [$order, $reshipment]) }}" method="POST" onsubmit="return confirm('Annuler cette demande ? Le lien ne fonctionnera plus.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:underline">Annuler</button>
                    </form>
                </div>
            @endif
        </div>
    @endforeach

    @if(! $pending)
        <button type="button" @click="open = !open"
                class="flex items-center gap-2 w-full py-2 px-3 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Demander une réexpédition
        </button>

        <form x-show="open" x-cloak action="{{ route('admin.orders.reshipments.store', $order) }}" method="POST"
              class="mt-2 p-3 rounded-lg bg-gray-50 space-y-2"
              onsubmit="return confirm('Créer la demande et envoyer le lien par email au client ?')">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 mb-1">Frais de réexpédition (€)</label>
                <input type="number" name="amount" step="0.01" min="0.5" value="{{ old('amount', number_format($defaultAmount, 2, '.', '')) }}" required class="{{ $inputClass }}">
                @error('amount')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Email du client</label>
                <input type="email" name="email" value="{{ old('email', $order->billing_email) }}" required class="{{ $inputClass }}">
                @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                <p class="mt-1 text-xs text-gray-400">Si vous modifiez l'adresse, elle sera enregistrée sur la commande.</p>
            </div>
            <button type="submit" class="w-full py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition">
                Créer le lien et envoyer l'email
            </button>
        </form>
    @endif
</div>
