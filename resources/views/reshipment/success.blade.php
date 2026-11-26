<x-layouts.app title="Réexpédition confirmée" :noindex="true">
<div class="max-w-2xl mx-auto px-4 py-16 text-center">

    @if($reshipment->isPaid())
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-4">
            <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-2xl font-semibold text-brand-900 mb-2" style="font-family: Georgia, serif;">Réexpédition confirmée !</h1>
        <p class="text-gray-600">
            Merci ! Nous préparons l'envoi de votre commande <strong>{{ $order->number }}</strong>.
            Vous recevrez un email avec le numéro de suivi dès que le colis sera expédié.
        </p>
    @elseif($reshipment->isCancelled())
        <h1 class="text-2xl font-semibold text-brand-900 mb-2" style="font-family: Georgia, serif;">Ce lien n'est plus valide</h1>
        <p class="text-gray-600">Cette demande de réexpédition a été annulée. Contactez-nous si besoin.</p>
    @else
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-100 mb-4">
            <svg class="w-8 h-8 text-yellow-600 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-semibold text-brand-900 mb-2" style="font-family: Georgia, serif;">Paiement en cours de vérification</h1>
        <p class="text-gray-600">
            Votre paiement est en cours de traitement. Vous recevrez une confirmation par email dès qu'il sera validé.
            Si rien n'arrive, vous pouvez
            <a href="{{ route('reshipment.show', $reshipment->token) }}" class="text-brand-700 underline">réessayer</a>.
        </p>
    @endif

    @if($reshipment->relay_point_name)
        <div class="bg-brand-50 rounded-xl p-6 text-left mt-8">
            <h2 class="text-sm font-semibold text-brand-900 mb-2">Point relais de retrait</h2>
            <p class="text-sm text-gray-700">{{ $reshipment->relay_point_name }}</p>
            <p class="text-xs text-gray-500">{{ $reshipment->relay_point_address }}</p>
            <p class="text-sm text-gray-700 mt-3">Frais de réexpédition : <strong>{{ $reshipment->formatted_amount }}</strong></p>
        </div>
    @endif

    <a href="{{ route('shop.index') }}" class="inline-block mt-8 text-sm text-brand-700 hover:underline">← Retour à la boutique</a>
</div>
</x-layouts.app>
