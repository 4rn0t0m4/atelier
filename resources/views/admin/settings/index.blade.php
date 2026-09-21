@extends('admin.layouts.app')

@section('content')
    <x-admin.page-breadcrumb title="Réglages" :breadcrumbs="['Réglages' => null]" />

    <div class="max-w-xl">

        @if(session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 mb-6">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h3 class="text-base font-semibold text-gray-800 mb-4">Limite de commandes</h3>

                <div class="mb-4">
                    <label for="daily_order_limit" class="block text-sm font-medium text-gray-700 mb-1">
                        Nombre maximum de commandes par jour
                    </label>
                    <input type="number" id="daily_order_limit" name="daily_order_limit"
                           value="{{ old('daily_order_limit', $settings['daily_order_limit']) }}"
                           min="0" max="9999"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    <p class="mt-1 text-xs text-gray-400">0 = pas de limite. Le compteur se remet à zéro à minuit.</p>
                    @error('daily_order_limit')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                @php
                    $todayCount = \App\Models\Order::whereDate('created_at', today())
                        ->whereIn('status', ['processing', 'completed', 'shipped'])
                        ->count();
                    $limit = (int) $settings['daily_order_limit'];
                @endphp

                <div class="rounded-lg bg-gray-50 p-3 text-sm text-gray-600">
                    Commandes aujourd'hui : <span class="font-semibold {{ $limit > 0 && $todayCount >= $limit ? 'text-red-600' : 'text-gray-900' }}">{{ $todayCount }}</span>
                    @if($limit > 0)
                        / {{ $limit }}
                        @if($todayCount >= $limit)
                            <span class="ml-2 text-xs text-red-600 font-medium">Limite atteinte</span>
                        @endif
                    @endif
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h3 class="text-base font-semibold text-gray-800 mb-1">Bandeau d'information</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Affiché en haut de toutes les pages de la boutique (fermeture de l'atelier, congés, délais…).
                </p>

                <label class="flex items-center gap-2 text-sm text-gray-700 mb-4">
                    <input type="hidden" name="closure_notice_enabled" value="0">
                    <input type="checkbox" name="closure_notice_enabled" value="1"
                           {{ old('closure_notice_enabled', $settings['closure_notice_enabled']) === '1' ? 'checked' : '' }}
                           class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    Afficher le bandeau
                </label>

                <div class="mb-4">
                    <label for="closure_notice_message" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                    <textarea id="closure_notice_message" name="closure_notice_message" rows="3" maxlength="300"
                              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('closure_notice_message', $settings['closure_notice_message']) }}</textarea>
                    <p class="mt-1 text-xs text-gray-400">300 caractères maximum.</p>
                    @error('closure_notice_message')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="closure_notice_until" class="block text-sm font-medium text-gray-700 mb-1">
                        Masquer automatiquement après le
                    </label>
                    <input type="date" id="closure_notice_until" name="closure_notice_until"
                           value="{{ old('closure_notice_until', $settings['closure_notice_until']) }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    <p class="mt-1 text-xs text-gray-400">Le bandeau reste visible jusqu'à la fin de cette journée. Laisser vide pour l'afficher sans limite.</p>
                    @error('closure_notice_until')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 transition">
                Enregistrer
            </button>
        </form>
    </div>
@endsection
