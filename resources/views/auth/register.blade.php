<x-layouts.guest title="Créer un compte">
<div class="bg-white rounded-xl shadow-sm border border-brand-100 p-8">
    <h1 class="text-xl font-semibold text-brand-900 mb-6 text-center" style="font-family: Georgia, serif;">
        Créer un compte
    </h1>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded px-4 py-3 mb-5">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('register.post') }}" method="POST" class="space-y-4">
        @csrf

        <div>
            <label class="block text-sm text-gray-700 mb-1">Prénom et nom</label>
            <input type="text" name="name" value="{{ old('name') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500"
                   required autofocus>
        </div>

        <div>
            <label class="block text-sm text-gray-700 mb-1">E-mail</label>
            <input type="email" name="email" value="{{ old('email') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500"
                   required>
        </div>

        <div>
            <label class="block text-sm text-gray-700 mb-1">Mot de passe</label>
            <input type="password" name="password"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500"
                   required>
        </div>

        <div>
            <label class="block text-sm text-gray-700 mb-1">Confirmer le mot de passe</label>
            <input type="password" name="password_confirmation"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500"
                   required>
        </div>

        <button type="submit"
                class="w-full bg-brand-700 text-white py-2.5 rounded font-medium hover:bg-brand-800 transition text-sm">
            Créer mon compte
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
        Déjà un compte ?
        <a href="{{ route('login') }}" class="text-brand-700 hover:underline">Se connecter</a>
    </p>
</div>
</x-layouts.guest>
