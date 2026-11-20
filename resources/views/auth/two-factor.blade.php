<x-layouts.guest title="Vérification">
<div class="bg-white rounded-xl shadow-sm border border-brand-100 p-8">
    <h1 class="text-xl font-semibold text-brand-900 mb-2 text-center" style="font-family: Georgia, serif;">
        Vérification en deux étapes
    </h1>
    <p class="text-sm text-gray-500 text-center mb-6">
        Saisissez le code à 6 chiffres affiché par votre application d'authentification.
    </p>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded px-4 py-3 mb-5">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('login.two-factor.verify') }}" method="POST" class="space-y-4" data-turbo="false">
        @csrf

        <div>
            <label for="code" class="block text-sm text-gray-700 mb-1">Code</label>
            <input type="text" name="code" id="code" inputmode="numeric" autocomplete="one-time-code"
                   autocapitalize="none" spellcheck="false" maxlength="20" placeholder="123 456"
                   class="w-full border border-gray-300 rounded px-3 py-2.5 text-lg tracking-widest text-center focus:ring-brand-500 focus:border-brand-500"
                   required autofocus>
            <p class="mt-1.5 text-xs text-gray-500">Téléphone indisponible ? Saisissez ici un de vos codes de secours.</p>
        </div>

        <label class="flex items-start gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="checkbox" name="trust" value="1" class="mt-0.5 rounded border-gray-300 text-brand-600">
            <span>Se souvenir de moi sur cet appareil pendant {{ $trustDays }} jours</span>
        </label>

        <button type="submit"
                class="w-full bg-brand-700 text-white py-2.5 rounded font-medium hover:bg-brand-800 transition text-sm">
            Valider
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
        <a href="{{ route('login') }}" class="text-brand-700 hover:underline">Revenir à la connexion</a>
    </p>
</div>
</x-layouts.guest>
