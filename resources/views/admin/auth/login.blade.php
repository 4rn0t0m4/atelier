@extends('admin.layouts.auth', ['title' => 'Connexion'])

@section('content')
    <form action="{{ route('admin.login.post') }}" method="POST" class="space-y-4" data-turbo="false">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" autocomplete="username"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:ring-brand-500 focus:border-brand-500"
                   required autofocus>
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
            <input type="password" name="password" id="password" autocomplete="current-password"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:ring-brand-500 focus:border-brand-500"
                   required>
        </div>

        {{-- Cochee par defaut : sans elle, l'app redemande le mot de passe apres 2 h d'inactivite --}}
        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input type="checkbox" name="remember" value="1" checked class="rounded border-gray-300 text-brand-600">
            Rester connecté sur cet appareil
        </label>

        <button type="submit" class="w-full py-2.5 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition">
            Se connecter
        </button>
    </form>

    <p class="mt-5 text-center text-sm">
        <a href="{{ route('password.request') }}" class="text-brand-700 hover:underline">Mot de passe oublié ?</a>
    </p>
@endsection
