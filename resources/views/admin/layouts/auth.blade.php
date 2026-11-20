<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Connexion' }} | Admin Atelier d'Aubin</title>

    @include('admin.layouts.pwa-meta')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50 min-h-screen flex flex-col justify-center">
    <div class="w-full max-w-sm mx-auto px-4 py-10">
        <img src="/images/pwa/icon-192.png" alt="" class="w-20 h-20 mx-auto rounded-2xl border border-gray-200">
        <p class="mt-3 text-center text-lg font-bold text-brand-900">Admin Atelier</p>

        <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6">
            @if (session('status'))
                <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800 mb-4">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 mb-4">{{ $errors->first() }}</div>
            @endif

            @yield('content')
        </div>
    </div>
</body>

</html>
