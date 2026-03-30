@extends('admin.layouts.app')

@section('content')
    <x-admin.page-breadcrumb title="Clients" :breadcrumbs="['Clients' => null]" />

    {{-- Search --}}
    <div class="rounded-2xl border border-gray-200 bg-white mb-6">
        <form action="{{ route('admin.customers.index') }}" method="GET" class="p-4 md:p-5">
            <div class="flex items-end gap-3">
                <div class="flex-1">
                    <label class="block text-xs text-gray-500 mb-1">Recherche</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Nom, email..."
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition">
                    Rechercher
                </button>
                @if(request('search'))
                    <a href="{{ route('admin.customers.index') }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700">Reinitialiser</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="rounded-2xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="px-5 py-3 text-left text-sm font-medium text-gray-500">Client</th>
                        <th class="px-5 py-3 text-left text-sm font-medium text-gray-500">Email</th>
                        <th class="px-5 py-3 text-right text-sm font-medium text-gray-500">Commandes</th>
                        <th class="px-5 py-3 text-right text-sm font-medium text-gray-500">Total depense</th>
                        <th class="px-5 py-3 text-right text-sm font-medium text-gray-500">Inscrit le</th>
                        <th class="px-5 py-3 text-right text-sm font-medium text-gray-500"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                            <td class="px-5 py-4">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="text-sm font-medium text-gray-700 hover:text-brand-600">
                                    {{ $customer->first_name }} {{ $customer->last_name }}
                                </a>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-500">
                                {{ $customer->email }}
                            </td>
                            <td class="px-5 py-4 text-sm text-right text-gray-700">
                                {{ $customer->orders_count }}
                            </td>
                            <td class="px-5 py-4 text-sm text-right font-medium text-gray-700">
                                {{ number_format($customer->total_spent ?? 0, 2, ',', ' ') }} &euro;
                            </td>
                            <td class="px-5 py-4 text-sm text-right text-gray-500">
                                {{ $customer->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="text-gray-400 hover:text-brand-600" title="Voir">
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
                                Aucun client trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
            <div class="px-5 py-4 border-t border-gray-200">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
@endsection
