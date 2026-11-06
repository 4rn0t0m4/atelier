@php
    /**
     * Bouton « Renvoyer un email » + mini-formulaire replie.
     * $panel = true dans le panneau lateral (soumission AJAX).
     *
     * @var \App\Models\Order $order
     */
    $panel = $panel ?? false;
    $defaultType = $order->shipped_at ? 'shipped' : 'confirmation';
    $inputClass = 'w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-brand-500 focus:border-brand-500';
@endphp

<div x-data="{ open: false }">
    <button type="button" @click="open = !open"
            class="flex items-center gap-2 w-full py-2 px-3 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition">
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
        Renvoyer un email
    </button>

    <form x-show="open" x-cloak
          action="{{ route('admin.orders.resend-email', $order) }}" method="POST"
          class="mt-2 p-3 rounded-lg bg-gray-50 space-y-2"
          @if($panel) data-panel-form data-confirm="Renvoyer cet email ?" @else onsubmit="return confirm('Renvoyer cet email ?')" @endif>
        @csrf

        <div>
            <label class="block text-xs text-gray-500 mb-1">Message</label>
            <select name="type" class="{{ $inputClass }}">
                @foreach(['confirmation' => 'Confirmation de commande', 'shipped' => "Avis d'expédition", 'review' => "Demande d'avis"] as $val => $lbl)
                    <option value="{{ $val }}" {{ $defaultType === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">Destinataire</label>
            <input type="email" name="email" value="{{ $order->billing_email }}" required class="{{ $inputClass }}">
            <p class="mt-1 text-xs text-gray-400">Si vous modifiez l'adresse, elle sera enregistrée sur la commande.</p>
        </div>

        <button type="submit" class="w-full py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition">
            Envoyer
        </button>
    </form>
</div>
