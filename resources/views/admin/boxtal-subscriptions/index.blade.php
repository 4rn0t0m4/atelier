@extends('admin.layouts.app')

@section('content')
    <x-admin.page-breadcrumb title="Webhooks Boxtal" :breadcrumbs="['Webhooks Boxtal' => null]" />

    <div class="max-w-2xl space-y-6">

        {{-- Messages flash --}}
        @if(session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        {{-- Résultats de création --}}
        @if(session('results'))
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                <h4 class="mb-2 font-semibold text-blue-900">Résultat des souscriptions</h4>
                @foreach(session('results') as $result)
                    <div class="mb-2 text-sm">
                        <span class="font-medium">{{ $result['eventType'] }}</span> :
                        @if($result['success'])
                            <span class="text-green-700">Créée</span>
                            @if($result['webhookSecret'] ?? null)
                                <div class="mt-1 rounded bg-yellow-100 p-2 text-xs text-yellow-900">
                                    <strong>Webhook secret :</strong>
                                    <code class="break-all">{{ $result['webhookSecret'] }}</code>
                                    <br>Ajoute <code>BOXTAL_V3_WEBHOOK_SECRET={{ $result['webhookSecret'] }}</code> dans le <code>.env</code>
                                </div>
                            @endif
                        @else
                            <span class="text-red-700">Erreur : {{ $result['error'] }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Erreur de chargement --}}
        @if($error)
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                {{ $error }}
            </div>
        @endif

        {{-- Souscriptions existantes --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h3 class="mb-5 text-lg font-semibold text-gray-800">Souscriptions actives</h3>

            @if(count($subscriptions) > 0)
                <div class="space-y-3">
                    @foreach($subscriptions as $sub)
                        <div class="flex items-center justify-between rounded-lg border border-gray-100 p-3">
                            <div>
                                <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ ($sub['eventType'] ?? '') === 'TRACKING_CHANGED' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $sub['eventType'] ?? '-' }}
                                </span>
                                <p class="mt-1 text-xs text-gray-500 break-all">{{ $sub['callbackUrl'] ?? '-' }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.boxtal-subscriptions.destroy', $sub['id'] ?? 0) }}" onsubmit="return confirm('Supprimer cette souscription ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-red-600 hover:text-red-800">Supprimer</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">Aucune souscription.</p>
            @endif
        </div>

        {{-- Créer les souscriptions --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h3 class="mb-4 text-lg font-semibold text-gray-800">Créer les souscriptions</h3>
            <p class="mb-4 text-sm text-gray-500">
                Crée les souscriptions pour recevoir les notifications de suivi (TRACKING_CHANGED) et les documents d'expédition (DOCUMENT_CREATED).
            </p>

            <form method="POST" action="{{ route('admin.boxtal-subscriptions.store') }}">
                @csrf
                <div class="mb-4">
                    <label for="callback_url" class="mb-1 block text-sm font-medium text-gray-700">URL de callback</label>
                    <input type="url" id="callback_url" name="callback_url"
                        value="{{ old('callback_url', url('/api/boxtal/webhook')) }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500" />
                </div>

                <div class="mb-4">
                    <label for="event_types" class="mb-1 block text-sm font-medium text-gray-700">Types d'événements (séparés par des virgules)</label>
                    <input type="text" id="event_types" name="event_types"
                        placeholder="Laisse vide pour DOCUMENT_CREATED + TRACKING_CHANGED"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500" />
                </div>

                <div class="mb-4">
                    <label for="webhook_secret" class="mb-1 block text-sm font-medium text-gray-700">Webhook secret (optionnel, auto-généré si vide)</label>
                    <input type="text" id="webhook_secret" name="webhook_secret"
                        placeholder="Laisse vide pour en générer un automatiquement"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500" />
                </div>

                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 transition">
                    Créer les souscriptions
                </button>
            </form>
        </div>
    </div>
@endsection
