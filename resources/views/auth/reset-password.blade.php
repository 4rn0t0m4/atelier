<x-layouts.guest title="Réinitialiser le mot de passe">
<div class="bg-white rounded-xl shadow-sm border border-brand-100 p-8">
    <h1 class="text-xl font-semibold text-brand-900 mb-6 text-center" style="font-family: Georgia, serif;">
        Nouveau mot de passe
    </h1>

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded px-4 py-3 mb-5">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('password.update') }}" method="POST" class="space-y-4" data-turbo="false">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label class="block text-sm text-gray-700 mb-1">E-mail</label>
            <input type="email" name="email" value="{{ old('email', $email) }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500"
                   required>
        </div>

        <div>
            <label class="block text-sm text-gray-700 mb-1">Nouveau mot de passe</label>
            <input type="password" name="password"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500"
                   required autofocus>
        </div>

        <div>
            <label class="block text-sm text-gray-700 mb-1">Confirmer le mot de passe</label>
            <input type="password" name="password_confirmation"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500"
                   required>
        </div>

        <button type="submit"
                class="w-full bg-brand-700 text-white py-2.5 rounded font-medium hover:bg-brand-800 transition text-sm">
            Réinitialiser
        </button>
    </form>
</div>
</x-layouts.guest>
