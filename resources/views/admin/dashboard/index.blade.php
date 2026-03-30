@extends('admin.layouts.app')

@section('content')
    <x-admin.page-breadcrumb title="Tableau de bord" :breadcrumbs="['Tableau de bord' => null]" />

    {{-- Metric cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 md:gap-6">
        {{-- Orders --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm text-gray-500">Commandes</span>
                    <h4 class="mt-2 text-2xl font-bold text-gray-800">
                        {{ number_format($metrics['orders_count']) }}
                    </h4>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Revenue --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm text-gray-500">Chiffre d'affaires</span>
                    <h4 class="mt-2 text-2xl font-bold text-gray-800">
                        {{ number_format($metrics['revenue'], 2, ',', ' ') }} &euro;
                    </h4>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-50 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Products --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm text-gray-500">Produits actifs</span>
                    <h4 class="mt-2 text-2xl font-bold text-gray-800">
                        {{ number_format($metrics['products_count']) }}
                    </h4>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-orange-50 text-orange-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Customers --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-sm text-gray-500">Clients</span>
                    <h4 class="mt-2 text-2xl font-bold text-gray-800">
                        {{ number_format($metrics['customers_count']) }}
                    </h4>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent orders --}}
    <div class="mt-6">
        <div class="rounded-2xl border border-gray-200 bg-white">
            <div class="px-5 py-4 border-b border-gray-200 md:px-6 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Commandes recentes</h3>
                <a href="{{ route('admin.orders.index') }}" class="text-sm text-brand-600 hover:underline">Tout voir</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="px-5 py-3 text-left text-sm font-medium text-gray-500">N°</th>
                            <th class="px-5 py-3 text-left text-sm font-medium text-gray-500">Client</th>
                            <th class="px-5 py-3 text-left text-sm font-medium text-gray-500">Statut</th>
                            <th class="px-5 py-3 text-right text-sm font-medium text-gray-500">Total</th>
                            <th class="px-5 py-3 text-right text-sm font-medium text-gray-500">Date</th>
                            <th class="px-5 py-3 text-right text-sm font-medium text-gray-500"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOrders as $order)
                            <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                                <td class="px-5 py-4">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="text-sm font-medium text-brand-600 hover:underline">{{ $order->number }}</a>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm text-gray-700">{{ $order->billing_first_name }} {{ $order->billing_last_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $order->billing_email }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <x-admin.badge :status="$order->status" />
                                </td>
                                <td class="px-5 py-4 text-sm text-right text-gray-700">
                                    {{ number_format($order->total, 2, ',', ' ') }} &euro;
                                </td>
                                <td class="px-5 py-4 text-sm text-right text-gray-500">
                                    {{ $order->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="text-gray-400 hover:text-brand-600" title="Voir">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-500">
                                    Aucune commande pour le moment.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
