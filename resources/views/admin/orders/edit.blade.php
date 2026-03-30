@extends('admin.layouts.app')

@section('content')
    <x-admin.page-breadcrumb title="Modifier {{ $order->number }}" :breadcrumbs="['Commandes' => route('admin.orders.index'), $order->number => route('admin.orders.show', $order), 'Modifier' => null]" />

    <div class="max-w-2xl">
        <form action="{{ route('admin.orders.update', $order) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="rounded-2xl border border-gray-200 bg-white p-5 space-y-4">
                <h3 class="text-base font-semibold text-gray-800">Informations</h3>

                <div>
                    <label class="block text-sm text-gray-600 mb-1">Statut</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                        @foreach(['pending' => 'Non reglée', 'processing' => 'En cours', 'shipped' => 'Expédiée', 'completed' => 'Terminée', 'cancelled' => 'Annulée', 'refunded' => 'Remboursée'] as $val => $lbl)
                            <option value="{{ $val }}" {{ $order->status === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-gray-600 mb-1">Transporteur</label>
                    <input type="text" name="tracking_carrier" value="{{ old('tracking_carrier', $order->tracking_carrier) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500"
                           placeholder="Colissimo, Mondial Relay...">
                </div>

                <div>
                    <label class="block text-sm text-gray-600 mb-1">Numero de suivi</label>
                    <input type="text" name="tracking_number" value="{{ old('tracking_number', $order->tracking_number) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>

                <div>
                    <label class="block text-sm text-gray-600 mb-1">Note interne</label>
                    <textarea name="customer_note" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('customer_note', $order->customer_note) }}</textarea>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="px-5 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition">
                    Enregistrer
                </button>
                <a href="{{ route('admin.orders.show', $order) }}" class="px-5 py-2 text-sm text-gray-500 hover:text-gray-700">Annuler</a>
            </div>
        </form>
    </div>
@endsection
