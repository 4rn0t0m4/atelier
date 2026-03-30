@extends('admin.layouts.app')

@section('content')
<x-admin.page-breadcrumb title="Codes promo" :breadcrumbs="['Boutique' => '', 'Codes promo' => '']" />

<div class="p-6">
    <x-admin.alert />

    <div class="flex justify-between items-center mb-6">
        <p class="text-sm text-gray-500">{{ $discounts->total() }} réduction(s)</p>
        <a href="{{ route('admin.discounts.create') }}"
           class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-brand-700 rounded-lg hover:bg-brand-800 transition">
            + Nouvelle réduction
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-6 py-3 text-left">Nom</th>
                    <th class="px-6 py-3 text-left">Code</th>
                    <th class="px-6 py-3 text-center">Réduction</th>
                    <th class="px-6 py-3 text-center">Type</th>
                    <th class="px-6 py-3 text-center">Période</th>
                    <th class="px-6 py-3 text-center">Utilisation</th>
                    <th class="px-6 py-3 text-center">Statut</th>
                    <th class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($discounts as $discount)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3">
                        <a href="{{ route('admin.discounts.edit', $discount) }}" class="font-medium text-gray-900 hover:text-brand-700">
                            {{ $discount->name }}
                        </a>
                    </td>
                    <td class="px-6 py-3">
                        @if($discount->coupon_code)
                            <code class="px-2 py-0.5 bg-gray-100 rounded text-xs font-mono uppercase">{{ $discount->coupon_code }}</code>
                        @else
                            <span class="text-gray-400 text-xs italic">Automatique</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-center font-medium">
                        @if($discount->discount_type === 'percentage')
                            {{ number_format($discount->discount_amount, 0) }} %
                        @else
                            {{ number_format($discount->discount_amount, 2, ',', ' ') }} €
                        @endif
                        @if($discount->free_shipping)
                            <span class="block text-xs text-green-600">+ livraison offerte</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-center text-xs text-gray-500">
                        {{ $discount->type === 'coupon' ? 'Coupon' : 'Auto' }}
                    </td>
                    <td class="px-6 py-3 text-center text-xs text-gray-500">
                        @if($discount->starts_at || $discount->ends_at)
                            {{ $discount->starts_at?->format('d/m/Y') ?? '…' }}
                            →
                            {{ $discount->ends_at?->format('d/m/Y') ?? '…' }}
                        @else
                            <span class="text-gray-300">Illimité</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-center text-xs text-gray-500">
                        {{ $discount->usage_count }}
                        @if($discount->usage_limit)
                            / {{ $discount->usage_limit }}
                        @endif
                    </td>
                    <td class="px-6 py-3 text-center">
                        @if($discount->is_active && $discount->isValid())
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-green-50 text-green-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Actif
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-red-50 text-red-600">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span> Inactif
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('admin.discounts.edit', $discount) }}" class="text-brand-600 hover:text-brand-800 mr-2">Modifier</a>
                        <form action="{{ route('admin.discounts.destroy', $discount) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer cette réduction ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:text-red-700">Supprimer</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-6 py-8 text-center text-gray-400">Aucune réduction.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($discounts->hasPages())
    <div class="mt-6">{{ $discounts->links() }}</div>
    @endif
</div>
@endsection
