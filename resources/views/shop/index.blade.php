<x-layouts.app
    :title="$currentCategory ? ($currentCategory->meta_title ?: $currentCategory->name) : 'Boutique'"
    :meta-description="$currentCategory ? ($currentCategory->meta_description ?: 'Decouvrez nos creations artisanales en bois — Atelier d\'Aubin') : 'Decouvrez nos creations artisanales en bois personnalisables. Decorations, cadeaux et objets uniques faits main — Atelier d\'Aubin.'"
    :canonical="$currentCategory ? $currentCategory->url() : route('shop.index')">

    @push('json-ld')
    <script type="application/ld+json">
    @php
        $breadcrumbItems = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Boutique', 'item' => route('shop.index')],
        ];
        if (isset($currentCategory) && $currentCategory) {
            if ($currentCategory->parent) {
                $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $currentCategory->parent->name, 'item' => $currentCategory->parent->url()];
                $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => 4, 'name' => $currentCategory->name];
            } else {
                $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $currentCategory->name];
            }
        }
    @endphp
    {!! json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $breadcrumbItems], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @endpush

    {{-- Produits mis en avant --}}
    @if(isset($featuredProducts) && $featuredProducts->isNotEmpty())
    <section class="py-10 bg-brand-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-xl font-semibold mb-6 text-brand-700" style="font-family: Georgia, serif;">
                Nos coups de coeur
            </h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
                @foreach($featuredProducts as $product)
                    <x-product-card :product="$product"/>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- Titre + recherche --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
            <h1 class="text-2xl font-semibold text-gray-900">
                @if($currentCategory) {{ $currentCategory->name }}
                @else Boutique
                @endif
            </h1>
            <form action="{{ $currentCategory ? $currentCategory->url() : route('shop.index') }}" method="GET" class="flex items-center gap-2">
                <input type="search" name="q" value="{{ request('q') }}"
                       placeholder="Rechercher…"
                       aria-label="Rechercher un produit"
                       class="border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 w-44">
                <button class="bg-brand-600 text-white px-3 py-1.5 rounded text-sm hover:opacity-90 transition">
                    OK
                </button>
            </form>
        </div>

        <div class="flex flex-col lg:flex-row gap-8" x-data="{ filtersOpen: false }">

            {{-- Bouton filtres mobile --}}
            <button @click="filtersOpen = !filtersOpen"
                    class="lg:hidden flex items-center gap-2 text-sm font-medium px-3 py-2 rounded-lg border border-gray-300 hover:border-gray-400 transition self-start text-brand-700">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filtrer
            </button>

            {{-- Sidebar categories --}}
            <aside class="lg:w-56 flex-shrink-0" :class="filtersOpen ? 'block' : 'hidden lg:block'" style="position: sticky; top: 100px; align-self: flex-start;"
                   x-cloak>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-3">Categories</p>
                <ul class="space-y-1 text-sm">
                    <li>
                        <a href="{{ route('shop.index') }}"
                           class="block px-2 py-1 rounded {{ !$currentCategory ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-700 hover:text-brand-700' }}">
                            Tous les produits
                        </a>
                    </li>
                    @foreach($categories as $cat)
                        <li>
                            <a href="{{ $cat->url() }}"
                               class="block px-2 py-1 rounded {{ $currentCategory?->id === $cat->id ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-700 hover:text-brand-700' }}">
                                {{ $cat->name }}
                            </a>
                            @if($cat->children->isNotEmpty())
                                <ul class="ml-3 mt-1 space-y-1">
                                    @foreach($cat->children as $child)
                                        <li>
                                            <a href="{{ $child->url() }}"
                                               class="block px-2 py-1 rounded text-xs {{ $currentCategory?->id === $child->id ? 'text-brand-700 font-medium' : 'text-gray-500 hover:text-brand-700' }}">
                                                {{ $child->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </aside>

            {{-- Grille produits --}}
            <div class="flex-1">
                @include('shop.partials.grid')
            </div>

        </div>
    </div>
</x-layouts.app>
