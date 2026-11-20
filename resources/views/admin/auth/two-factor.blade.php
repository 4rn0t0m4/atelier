@extends('admin.layouts.auth', ['title' => 'Vérification'])

@section('content')
    <h1 class="text-base font-semibold text-gray-800 text-center">Vérification en deux étapes</h1>
    <p class="mt-1 mb-5 text-sm text-gray-500 text-center">
        Saisissez le code à 6 chiffres affiché par votre application d'authentification.
    </p>

    <form action="{{ route('admin.login.two-factor.verify') }}" method="POST" class="space-y-4" data-turbo="false">
        @csrf

        <div>
            <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Code</label>
            <input type="text" name="code" id="code" inputmode="numeric" autocomplete="one-time-code"
                   autocapitalize="none" spellcheck="false" maxlength="20" placeholder="123 456"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-lg tracking-widest text-center focus:ring-brand-500 focus:border-brand-500"
                   required autofocus>
            <p class="mt-1.5 text-xs text-gray-500">Téléphone indisponible ? Saisissez ici un de vos codes de secours.</p>
        </div>

        <label class="flex items-start gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="checkbox" name="trust" value="1" class="mt-0.5 rounded border-gray-300 text-brand-600">
            <span>Se souvenir de moi sur cet appareil pendant {{ $trustDays }} jours</span>
        </label>

        <button type="submit" class="w-full py-2.5 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition">
            Valider
        </button>
    </form>

    <p class="mt-5 text-center text-sm">
        <a href="{{ route('admin.login') }}" class="text-brand-700 hover:underline">Revenir à la connexion</a>
    </p>
@endsection
