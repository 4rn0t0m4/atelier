{{-- Suivi renseigne automatiquement par Boxtal (webhook) : lecture seule. --}}
@if($order->tracking_number)
    <div class="mb-4 rounded-lg bg-gray-50 p-3 space-y-1">
        <p class="text-xs text-gray-500">Suivi transmis par Boxtal</p>
        @if($order->tracking_carrier)
            <p class="text-sm text-gray-700">{{ $order->tracking_carrier }}</p>
        @endif
        <p class="font-mono text-xs text-gray-700 break-all">{{ $order->tracking_number }}</p>
        @if($order->tracking_url)
            <a href="{{ $order->tracking_url }}" target="_blank"
               class="inline-block text-xs text-brand-600 hover:underline">Suivre le colis</a>
        @endif
    </div>
@endif
