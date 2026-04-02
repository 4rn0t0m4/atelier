<x-layouts.guest title="Connexion">
<div class="bg-white rounded-xl shadow-sm border border-brand-100 p-8">
    <h1 class="text-xl font-semibold text-brand-900 mb-6 text-center" style="font-family: Georgia, serif;">
        Connexion
    </h1>

    @if(session('status'))
        <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded px-4 py-3 mb-5">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded px-4 py-3 mb-5">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('login.post') }}" method="POST" class="space-y-4" data-turbo="false">
        @csrf

        <div>
            <label for="email" class="block text-sm text-gray-700 mb-1">E-mail</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500"
                   required autofocus>
        </div>

        <div>
            <label for="password" class="block text-sm text-gray-700 mb-1">Mot de passe</label>
            <input type="password" name="password" id="password"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500"
                   required>
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-brand-600">
                Se souvenir de moi
            </label>
            <a href="{{ route('password.request') }}" class="text-sm text-brand-700 hover:underline">
                Mot de passe oublié ?
            </a>
        </div>

        <button type="submit"
                class="w-full bg-brand-700 text-white py-2.5 rounded font-medium hover:bg-brand-800 transition text-sm">
            Se connecter
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
        Pas encore de compte ?
        <a href="{{ route('register') }}" class="text-brand-700 hover:underline">Créer un compte</a>
    </p>
</div>
</x-layouts.guest>
