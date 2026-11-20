@extends('admin.layouts.app')

@section('content')
    <x-admin.page-breadcrumb title="Sécurité" :breadcrumbs="['Sécurité' => null]" />

    @php
        $inputClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500';
    @endphp

    <div class="max-w-xl space-y-6">

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
        @endif

        {{-- Codes de secours : affiches une seule fois, juste apres leur creation --}}
        @if (! empty($recoveryCodes))
            <div class="rounded-2xl border border-amber-300 bg-amber-50 p-5" x-data>
                <h3 class="text-base font-semibold text-amber-900">Vos codes de secours</h3>
                <p class="mt-1 text-sm text-amber-800">
                    Notez-les maintenant, ils ne seront plus affichés. Chacun remplace une fois le code du téléphone
                    s'il est perdu ou en panne.
                </p>
                <div class="mt-4 grid grid-cols-2 gap-2 font-mono text-sm text-gray-800">
                    @foreach ($recoveryCodes as $code)
                        <div class="rounded-lg bg-white border border-amber-200 px-3 py-2 text-center">{{ $code }}</div>
                    @endforeach
                </div>
                <button type="button" data-copy="{{ implode("\n", $recoveryCodes) }}"
                    @click="navigator.clipboard.writeText($el.dataset.copy); $el.textContent = 'Copié !'; setTimeout(() => $el.textContent = 'Copier les codes', 1500)"
                    class="mt-4 w-full py-2 border border-amber-300 bg-white rounded-lg text-sm font-medium text-amber-900 hover:bg-amber-100 transition">
                    Copier les codes
                </button>
            </div>
        @endif

        @if ($enabled)
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-gray-800">Double authentification</h3>
                    <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-medium text-green-700">Activée</span>
                </div>
                <p class="mt-2 text-sm text-gray-600">
                    À chaque connexion, un code de votre application d'authentification est demandé en plus du mot de passe,
                    sauf sur les appareils mémorisés ({{ \App\Services\TwoFactorService::TRUST_DAYS }} jours).
                </p>
                <p class="mt-2 text-sm text-gray-600">
                    Codes de secours restants :
                    <span class="font-semibold {{ $remainingRecoveryCodes <= 2 ? 'text-red-600' : 'text-gray-900' }}">{{ $remainingRecoveryCodes }}</span>
                </p>
                <p class="mt-2 text-xs text-gray-400">
                    Changer votre mot de passe déconnecte tous les appareils et oublie ceux qui étaient mémorisés.
                </p>
            </div>

            <form method="POST" action="{{ route('admin.security.recovery-codes') }}" class="rounded-2xl border border-gray-200 bg-white p-5 space-y-3">
                @csrf
                <h3 class="text-base font-semibold text-gray-800">Nouveaux codes de secours</h3>
                <p class="text-sm text-gray-600">Les codes actuels cesseront de fonctionner.</p>
                <div>
                    <label for="regenerate_password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                    <input type="password" id="regenerate_password" name="current_password" autocomplete="current-password" class="{{ $inputClass }}" required>
                </div>
                <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition">
                    Générer de nouveaux codes
                </button>
            </form>

            <form method="POST" action="{{ route('admin.security.disable') }}" class="rounded-2xl border border-gray-200 bg-white p-5 space-y-3"
                  onsubmit="return confirm('Désactiver la double authentification ?')">
                @csrf
                @method('DELETE')
                <h3 class="text-base font-semibold text-gray-800">Désactiver</h3>
                <p class="text-sm text-gray-600">Le compte ne sera plus protégé que par son mot de passe.</p>
                <div>
                    <label for="disable_password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                    <input type="password" id="disable_password" name="current_password" autocomplete="current-password" class="{{ $inputClass }}" required>
                </div>
                <button type="submit" class="px-4 py-2 border border-red-200 text-red-600 rounded-lg text-sm font-medium hover:bg-red-50 transition">
                    Désactiver la double authentification
                </button>
            </form>

        @else
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-gray-800">Double authentification</h3>
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Désactivée</span>
                </div>
                <p class="mt-2 text-sm text-gray-600">
                    Protège le compte même si le mot de passe est volé : la connexion demande en plus un code
                    à 6 chiffres généré par votre téléphone.
                </p>

                <ol class="mt-5 space-y-5 text-sm text-gray-700">
                    <li>
                        <p class="font-medium text-gray-800">1. Installez une application d'authentification</p>
                        <p class="mt-1 text-gray-600">Google Authenticator, ou toute application équivalente.</p>
                    </li>
                    <li x-data>
                        <p class="font-medium text-gray-800">2. Ajoutez-y ce compte</p>
                        <p class="mt-1 text-gray-600">Depuis un ordinateur, scannez ce QR code avec l'application :</p>
                        <div class="mt-3 inline-block rounded-xl border border-gray-200 bg-white p-2">{!! $qrCode !!}</div>

                        <p class="mt-3 text-gray-600">Sur ce téléphone, ouvrez directement l'application :</p>
                        <a href="{{ $otpauthUrl }}"
                           class="mt-2 flex items-center justify-center w-full py-2.5 border border-brand-200 rounded-lg text-sm font-medium text-brand-700 hover:bg-brand-50 transition">
                            Ouvrir mon application d'authentification
                        </a>

                        <p class="mt-3 text-gray-600">Ou saisissez cette clé à la main :</p>
                        <div class="mt-2 flex items-center gap-2">
                            <code class="flex-1 rounded-lg bg-gray-100 px-3 py-2 text-xs break-all">{{ $secret }}</code>
                            <button type="button" data-copy="{{ $secret }}"
                                @click="navigator.clipboard.writeText($el.dataset.copy); $el.textContent = 'Copié !'; setTimeout(() => $el.textContent = 'Copier', 1500)"
                                class="shrink-0 px-3 py-2 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition">Copier</button>
                        </div>
                    </li>
                    <li>
                        <p class="font-medium text-gray-800">3. Confirmez</p>
                        <form method="POST" action="{{ route('admin.security.enable') }}" class="mt-2 space-y-3">
                            @csrf
                            <div>
                                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Code affiché par l'application</label>
                                <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="10"
                                       placeholder="123 456" class="{{ $inputClass }}" required>
                            </div>
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Votre mot de passe</label>
                                <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                                       class="{{ $inputClass }}" required>
                            </div>
                            <button type="submit" class="w-full py-2.5 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition">
                                Activer la double authentification
                            </button>
                        </form>
                    </li>
                </ol>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.security.password') }}" class="rounded-2xl border border-gray-200 bg-white p-5 space-y-3">
            @csrf
            @method('PUT')
            <h3 class="text-base font-semibold text-gray-800">Mot de passe</h3>
            <p class="text-sm text-gray-600">Le changer déconnecte tous les autres appareils.</p>
            <div>
                <label for="password_current" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe actuel</label>
                <input type="password" id="password_current" name="current_password" autocomplete="current-password" class="{{ $inputClass }}" required>
            </div>
            <div>
                <label for="password_new" class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe</label>
                <input type="password" id="password_new" name="password" autocomplete="new-password" minlength="8" class="{{ $inputClass }}" required>
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmer le nouveau mot de passe</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" minlength="8" class="{{ $inputClass }}" required>
            </div>
            <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition">
                Changer le mot de passe
            </button>
        </form>
    </div>
@endsection
