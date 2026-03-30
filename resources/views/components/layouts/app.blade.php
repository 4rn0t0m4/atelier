<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Favicon --}}
    <link rel="icon" href="/favicon.ico" sizes="any">

    @stack('head')

    @php
        $pageTitle = ($title ?? config('app.name')) . " — Atelier d'Aubin";
        $pageDesc  = $metaDescription ?? "Atelier d'Aubin — Objets décoratifs en bois faits main. Découvrez nos créations artisanales personnalisables.";
        $pageImage = $ogImage ?? asset('images/og-default.jpg');
    @endphp
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDesc }}">
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:title" content="{{ $title ?? config('app.name') }}">
    <meta property="og:description" content="{{ $pageDesc }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $pageImage }}">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="Atelier d'Aubin">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? config('app.name') }}">
    <meta name="twitter:description" content="{{ $pageDesc }}">
    <meta name="twitter:image" content="{{ $pageImage }}">

    {{-- Schema.org --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => url('/') . '#organization',
                'name' => "Atelier d'Aubin",
                'url' => url('/'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('images/logo.png'),
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => url('/') . '#website',
                'name' => "Atelier d'Aubin",
                'url' => url('/'),
                'publisher' => ['@id' => url('/') . '#organization'],
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => url('/boutique') . '?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>

    @stack('json-ld')

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @include('partials.tracking')
</head>
<body class="bg-white text-gray-900 font-sans antialiased" x-data>

    {{-- Header --}}
    @include('partials.header')

    {{-- Contenu principal --}}
    <main id="main-content" class="min-h-screen">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    @include('partials.footer')

    {{-- Notifications flash --}}
    @include('partials.flash')

</body>
</html>
