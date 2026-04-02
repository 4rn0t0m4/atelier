@props(['title' => null])
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Google Analytics --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-MVM8MP7KTF"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-MVM8MP7KTF');
    </script>

    <title>{{ $title ? $title . ' — ' : '' }}Atelier d'Aubin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-brand-50 text-gray-900 font-sans antialiased min-h-screen flex flex-col justify-center">

    <div class="w-full max-w-md mx-auto px-4 py-12">
        <a href="{{ route('home') }}" class="block text-center mb-8">
            <span class="text-xl font-semibold text-brand-800" style="font-family: Georgia, serif;">
                Atelier d'Aubin
            </span>
        </a>
        {{ $slot }}
    </div>

</body>
</html>
