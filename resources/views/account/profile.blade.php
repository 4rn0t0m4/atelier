<x-layouts.app title="Mon profil" :noindex="true">
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    <div class="flex items-center gap-3 mb-8">
        <a href="{{ route('account.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">Mon compte</a>
        <span class="text-gray-300">/</span>
        <h1 class="text-2xl font-semibold text-brand-900" style="font-family: Georgia, serif;">Mon profil</h1>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded px-4 py-3 mb-6">
            {{ session('success') }}
        </div>
    @endif

    {{-- Informations personnelles --}}
    <section class="bg-white border border-brand-100 rounded-xl p-6 mb-6">
        <h2 class="text-base font-semibold text-brand-900 mb-5">Informations personnelles</h2>

        <form action="{{ route('account.profile.update') }}" method="POST" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-sm text-gray-700 mb-1">Nom complet</label>
                <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500 @error('name') border-red-400 @enderror"
                       required>
                @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm text-gray-700 mb-1">E-mail</label>
                <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500 @error('email') border-red-400 @enderror"
                       required>
                @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="bg-brand-700 text-white py-2 px-5 rounded font-medium hover:bg-brand-800 transition text-sm">
                    Enregistrer
                </button>
            </div>
        </form>
    </section>

    {{-- Mot de passe --}}
    <section class="bg-white border border-brand-100 rounded-xl p-6">
        <h2 class="text-base font-semibold text-brand-900 mb-5">Changer le mot de passe</h2>

        <form action="{{ route('account.password.update') }}" method="POST" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-sm text-gray-700 mb-1">Mot de passe actuel</label>
                <input type="password" name="current_password"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500 @error('current_password') border-red-400 @enderror"
                       required>
                @error('current_password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm text-gray-700 mb-1">Nouveau mot de passe</label>
                <input type="password" name="password"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500 @error('password') border-red-400 @enderror"
                       required>
                @error('password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm text-gray-700 mb-1">Confirmer le nouveau mot de passe</label>
                <input type="password" name="password_confirmation"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500"
                       required>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="bg-brand-700 text-white py-2 px-5 rounded font-medium hover:bg-brand-800 transition text-sm">
                    Modifier
                </button>
            </div>
        </form>
    </section>
</div>
</x-layouts.app>
