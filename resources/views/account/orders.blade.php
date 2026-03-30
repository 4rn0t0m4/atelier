<x-layouts.app title="Mes commandes" :noindex="true">
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="flex items-center gap-3 mb-8">
        <a href="{{ route('account.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">Mon compte</a>
        <span class="text-gray-300">/</span>
        <h1 class="text-2xl font-semibold text-brand-900" style="font-family: Georgia, serif;">Mes commandes</h1>
    </div>

    @if($orders->isEmpty())
        <div class="text-center py-12 text-gray-400">
            <p class="mb-4">Vous n'avez pas encore passé de commande.</p>
            <a href="{{ route('shop.index') }}"
               class="inline-block bg-brand-700 text-white px-5 py-2 rounded font-medium hover:bg-brand-800 transition text-sm">
                Découvrir la boutique
            </a>
        </div>
    @else
        <div class="bg-white border border-brand-100 rounded-xl overflow-hidden mb-6">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-brand-100 text-gray-500 text-xs uppercase tracking-wide">
                        <th class="text-left px-4 py-3">Commande</th>
                        <th class="text-left px-4 py-3">Date</th>
                        <th class="text-left px-4 py-3">Statut</th>
                        <th class="text-right px-4 py-3">Total</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($orders as $order)
                        <tr class="hover:bg-brand-50/50">
                            <td class="px-4 py-3 font-medium">{{ $order->number }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $order->created_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                @include('account.partials.order-status', ['status' => $order->status])
                            </td>
                            <td class="px-4 py-3 text-right">{{ number_format($order->total, 2, ',', ' ') }} €</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('account.order', $order) }}"
                                   class="text-xs text-brand-700 hover:underline">Détails</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $orders->links() }}
    @endif
</div>
</x-layouts.app>
