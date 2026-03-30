@extends('admin.layouts.app')

@section('content')
    <x-admin.page-breadcrumb title="{{ $user->first_name }} {{ $user->last_name }}" :breadcrumbs="['Clients' => route('admin.customers.index'), $user->first_name.' '.$user->last_name => null]" />

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Customer info --}}
        <div class="space-y-6">
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h3 class="text-sm font-semibold text-gray-800 mb-4">Informations</h3>
                <div class="space-y-3 text-sm">
                    <div>
                        <span class="text-gray-500">Email</span>
                        <p class="text-gray-700">{{ $user->email }}</p>
                    </div>
                    @if($user->phone)
                        <div>
                            <span class="text-gray-500">Telephone</span>
                            <p class="text-gray-700">{{ $user->phone }}</p>
                        </div>
                    @endif
                    <div>
                        <span class="text-gray-500">Inscrit le</span>
                        <p class="text-gray-700">{{ $user->created_at->format('d/m/Y a H:i') }}</p>
                    </div>
                </div>
            </div>

            @if($user->address_1)
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3">Adresse</h3>
                    <div class="text-sm text-gray-600 space-y-1">
                        <p>{{ $user->address_1 }}</p>
                        @if($user->address_2)<p>{{ $user->address_2 }}</p>@endif
                        <p>{{ $user->postcode }} {{ $user->city }}</p>
                        <p>{{ $user->country }}</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Orders --}}
        <div class="xl:col-span-2">
            <div class="rounded-2xl border border-gray-200 bg-white">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-800">Commandes ({{ $orders->count() }})</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="px-5 py-3 text-left text-sm font-medium text-gray-500">N°</th>
                                <th class="px-5 py-3 text-left text-sm font-medium text-gray-500">Statut</th>
                                <th class="px-5 py-3 text-right text-sm font-medium text-gray-500">Total</th>
                                <th class="px-5 py-3 text-right text-sm font-medium text-gray-500">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orders as $order)
                                <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="text-sm font-medium text-brand-600 hover:underline">{{ $order->number }}</a>
                                    </td>
                                    <td class="px-5 py-3">
                                        <x-admin.badge :status="$order->status" />
                                    </td>
                                    <td class="px-5 py-3 text-sm text-right text-gray-700">
                                        {{ number_format($order->total, 2, ',', ' ') }} &euro;
                                    </td>
                                    <td class="px-5 py-3 text-sm text-right text-gray-500">
                                        {{ $order->created_at->format('d/m/Y') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-8 text-center text-sm text-gray-500">
                                        Aucune commande.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
