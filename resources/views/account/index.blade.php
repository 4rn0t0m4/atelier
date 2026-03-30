<x-layouts.app title="Mon compte" :noindex="true">
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <h1 class="text-2xl font-semibold text-brand-900 mb-8" style="font-family: Georgia, serif;">Mon compte</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <a href="{{ route('account.orders') }}"
           class="bg-white border border-brand-100 rounded-xl p-5 hover:border-brand-300 hover:shadow-sm transition">
            <div class="text-brand-700 mb-2">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <h2 class="font-medium text-gray-900">Mes commandes</h2>
            <p class="text-sm text-gray-500 mt-1">Historique et suivi</p>
        </a>

        <a href="{{ route('account.profile') }}"
           class="bg-white border border-brand-100 rounded-xl p-5 hover:border-brand-300 hover:shadow-sm transition">
            <div class="text-brand-700 mb-2">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <h2 class="font-medium text-gray-900">Mon profil</h2>
            <p class="text-sm text-gray-500 mt-1">Informations personnelles</p>
        </a>

        <a href="{{ route('account.address') }}"
           class="bg-white border border-brand-100 rounded-xl p-5 hover:border-brand-300 hover:shadow-sm transition">
            <div class="text-brand-700 mb-2">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <h2 class="font-medium text-gray-900">Mes coordonnées</h2>
            <p class="text-sm text-gray-500 mt-1">Adresse de facturation</p>
        </a>

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit"
                    class="w-full text-left bg-white border border-gray-100 rounded-xl p-5 hover:border-red-200 hover:shadow-sm transition">
                <div class="text-gray-400 mb-2">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </div>
                <h2 class="font-medium text-gray-900">Déconnexion</h2>
                <p class="text-sm text-gray-500 mt-1">Quitter mon compte</p>
            </button>
        </form>
    </div>

    {{-- Dernières commandes --}}
    @if($orders->isNotEmpty())
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-brand-900">Dernières commandes</h2>
                <a href="{{ route('account.orders') }}" class="text-sm text-brand-700 hover:underline">
                    Tout voir
                </a>
            </div>
            <div class="bg-white border border-brand-100 rounded-xl overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-brand-100 text-gray-500 text-xs uppercase tracking-wide">
                            <th class="text-left px-4 py-3">Commande</th>
                            <th class="text-left px-4 py-3">Date</th>
                            <th class="text-left px-4 py-3">Statut</th>
                            <th class="text-right px-4 py-3">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($orders as $order)
                            <tr class="hover:bg-brand-50/50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('account.order', $order) }}"
                                       class="text-brand-700 hover:underline">{{ $order->number }}</a>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $order->created_at->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    @include('account.partials.order-status', ['status' => $order->status])
                                </td>
                                <td class="px-4 py-3 text-right font-medium">
                                    {{ number_format($order->total, 2, ',', ' ') }} €
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @else
        <div class="text-center py-12 text-gray-400">
            <p class="mb-4">Vous n'avez pas encore passé de commande.</p>
            <a href="{{ route('shop.index') }}"
               class="inline-block bg-brand-700 text-white px-5 py-2 rounded font-medium hover:bg-brand-800 transition text-sm">
                Découvrir la boutique
            </a>
        </div>
    @endif
</div>
</x-layouts.app>
