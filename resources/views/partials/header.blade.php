@php
    $mainMenu = \App\Helpers\MenuHelper::getMainMenu();
@endphp

{{-- Barre utilitaire --}}
<div class="bg-white border-b border-gray-200 text-xs tracking-wider uppercase">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-center gap-4 sm:gap-6 py-2 flex-wrap">
        <a href="/livraison" class="text-gray-600 hover:text-gray-900 transition">Livraison</a>
        <a href="/contact" class="text-gray-600 hover:text-gray-900 transition">Contact</a>
        <a href="{{ route('cart.index') }}" class="text-gray-600 hover:text-gray-900 transition">Panier</a>
        @auth
            <a href="{{ route('account.index') }}" class="text-gray-600 hover:text-gray-900 transition">Mon compte</a>
            <form action="{{ route('logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="text-gray-600 hover:text-gray-900 transition uppercase text-xs tracking-wider">Déconnexion</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900 transition">Connexion / Inscription</a>
        @endauth
    </div>
</div>

{{-- Bannière avec logo --}}
<div class="relative flex items-center justify-center"
     x-data="{ y: 0 }"
     x-on:scroll.window.throttle.10ms="y = window.scrollY"
     :style="'height: 300px; background-image: url({{ asset('images/header-banner.jpg') }}); background-size: cover; background-position: center ' + (5 + y * 0.18) + '%;'"
     style="height: 300px; background-image: url('{{ asset('images/header-banner.jpg') }}'); background-size: cover; background-position: center 5%;">
    <a href="{{ route('home') }}" class="relative z-10">
        <img src="{{ asset('images/logo.png') }}" alt="Atelier d'Aubin"
             style="max-width: 250px; height: auto;">
    </a>
</div>

{{-- Navigation principale --}}
<header class="sticky top-0 z-50 bg-white border-b border-gray-200"
        x-data="{ mobileOpen: false, openMenu: null }"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-center h-14">

            {{-- Burger mobile --}}
            <button class="md:hidden absolute left-4 p-1 rounded-md hover:bg-brand-50 transition-colors"
                    @click="mobileOpen = !mobileOpen" aria-label="Menu">
                <svg x-show="!mobileOpen" class="w-6 h-6 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg x-show="mobileOpen" x-cloak class="w-6 h-6 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            {{-- Nav desktop --}}
            <nav class="hidden md:flex items-center gap-1 text-xs font-semibold uppercase tracking-wider text-gray-700">
                @foreach($mainMenu as $i => $item)
                    <div class="relative"
                         @mouseenter="openMenu = {{ $i }}"
                         @mouseleave="openMenu = null">
                        <a href="{{ $item['url'] }}"
                           class="flex items-center gap-1 px-4 py-4 hover:text-brand-700 transition-colors whitespace-nowrap">
                            {{ $item['name'] }}
                            @if(!empty($item['children']))
                                <svg class="w-3 h-3 shrink-0 transition-transform duration-200"
                                     :class="openMenu === {{ $i }} ? 'rotate-180' : ''"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            @endif
                        </a>
                        @if(!empty($item['children']))
                            <div x-show="openMenu === {{ $i }}" x-cloak
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 -translate-y-1"
                                 class="absolute left-0 top-full bg-white shadow-lg border border-gray-200 rounded-b-lg min-w-[220px] z-50">
                                <div class="py-2">
                                    @foreach($item['children'] as $child)
                                        <a href="{{ $child['url'] }}"
                                           class="block px-5 py-2 text-sm font-normal normal-case tracking-normal text-gray-600 hover:text-brand-700 hover:bg-brand-50 transition">
                                            {{ $child['name'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </nav>

            {{-- Recherche + Panier --}}
            <div class="absolute right-4 md:left-4 md:right-auto flex items-center gap-3">
                {{-- Recherche --}}
                <button @click="$dispatch('toggle-search')" class="flex items-center p-1 text-gray-500 hover:text-gray-700 transition" title="Rechercher" aria-label="Rechercher">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>

                {{-- Panier --}}
                <div class="relative">
                    <button @click="$dispatch('toggle-cart')"
                            class="relative flex items-center p-1 text-gray-700 hover:text-brand-700 transition" title="Mon panier" aria-label="Mon panier">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <turbo-frame id="cart-count">
                            @php $count = session('cart') ? array_sum(array_column(session('cart'), 'quantity')) : 0; @endphp
                            @if($count > 0)
                                <span class="absolute -top-1 -right-1 bg-brand-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center font-bold leading-none"
                                      style="font-size: 10px;">{{ $count }}</span>
                            @endif
                        </turbo-frame>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Nav mobile --}}
    <div x-show="mobileOpen" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="md:hidden border-t border-gray-200 px-4 py-4 space-y-1 bg-white max-h-[80vh] overflow-y-auto">

        @foreach($mainMenu as $item)
            <div x-data="{ subOpen: false }">
                @if(!empty($item['children']))
                    <button @click="subOpen = !subOpen"
                            class="flex items-center justify-between w-full py-2.5 text-sm font-semibold uppercase tracking-wide border-b border-gray-100 text-left text-gray-700">
                        {{ $item['name'] }}
                        <svg class="w-4 h-4 shrink-0 transition-transform duration-200" :class="subOpen ? 'rotate-180' : ''"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="subOpen" x-cloak x-transition class="pl-4 pb-2">
                        @if($item['url'] !== '#')
                            <a href="{{ $item['url'] }}" class="block py-2 text-sm font-medium text-brand-700">
                                Tout voir
                            </a>
                        @endif
                        @foreach($item['children'] as $child)
                            <a href="{{ $child['url'] }}" class="block py-1.5 text-sm text-gray-600">
                                {{ $child['name'] }}
                            </a>
                        @endforeach
                    </div>
                @else
                    <a href="{{ $item['url'] }}"
                       class="block py-2.5 text-sm font-semibold uppercase tracking-wide border-b border-gray-100 text-gray-700">
                        {{ $item['name'] }}
                    </a>
                @endif
            </div>
        @endforeach

        <div class="border-t border-gray-100 pt-3 space-y-1">
            @auth
                <a href="{{ route('account.index') }}" class="block py-2.5 text-sm font-medium text-gray-700">Mon compte</a>
            @else
                <a href="{{ route('login') }}" class="block py-2.5 text-sm text-gray-700">Connexion / Inscription</a>
            @endauth
            <a href="{{ route('cart.index') }}"
               class="block mt-2 text-center font-semibold py-2.5 rounded-lg text-white bg-brand-600">
                Mon panier
            </a>
        </div>
    </div>
</header>

{{-- Panel latéral panier (gauche) --}}
<div x-data="{ cartOpen: false, cartHtml: '' }"
     @toggle-cart.window="cartOpen = !cartOpen; if(cartOpen) fetch('{{ route('cart.mini') }}').then(r => r.text()).then(h => cartHtml = h)"
     @cart-added.window="cartOpen = true; fetch('{{ route('cart.mini') }}').then(r => r.text()).then(h => cartHtml = h)"
     x-cloak>

    {{-- Overlay --}}
    <div x-show="cartOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="cartOpen = false"
         class="fixed inset-0 bg-black/40 z-[60]"
         style="position:fixed;top:0;left:0;right:0;bottom:0"></div>

    {{-- Panel --}}
    <div x-show="cartOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed inset-y-0 left-0 w-80 sm:w-96 bg-white shadow-2xl z-[70] flex flex-col"
         style="position:fixed;top:0;bottom:0;left:0">

        {{-- En-tête panel --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-800">Mon panier</h2>
            <button @click="cartOpen = false" class="p-1 text-gray-400 hover:text-gray-600 transition" aria-label="Fermer">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Contenu --}}
        <div class="flex-1 overflow-y-auto" x-html="cartHtml"></div>
    </div>
</div>

{{-- Overlay recherche --}}
<div x-data="{
        searchOpen: false,
        query: '',
        results: [],
        loading: false,
        timeout: null,
        open() {
            this.searchOpen = true;
            this.$nextTick(() => this.$refs.searchInput.focus());
        },
        close() {
            this.searchOpen = false;
            this.query = '';
            this.results = [];
        },
        search() {
            clearTimeout(this.timeout);
            if (this.query.length < 2) { this.results = []; return; }
            this.loading = true;
            this.timeout = setTimeout(() => {
                fetch('{{ route('shop.search') }}?q=' + encodeURIComponent(this.query))
                    .then(r => r.json())
                    .then(data => { this.results = data; this.loading = false; })
                    .catch(() => { this.loading = false; });
            }, 250);
        }
     }"
     @toggle-search.window="searchOpen ? close() : open()"
     @keydown.escape.window="if(searchOpen) close()"
     x-cloak>

    {{-- Fond --}}
    <div x-show="searchOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="close()"
         class="fixed inset-0 bg-black/50 z-[80]"
         style="position:fixed;top:0;left:0;right:0;bottom:0"></div>

    {{-- Panneau recherche --}}
    <div x-show="searchOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-4"
         class="fixed inset-x-0 top-0 z-[90] bg-white shadow-2xl"
         style="position:fixed;top:0;left:0;right:0">

        <div class="max-w-2xl mx-auto px-4 py-6">
            {{-- Champ de recherche --}}
            <div class="relative">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input x-ref="searchInput" type="search" x-model="query" @input="search()"
                       placeholder="Rechercher un produit..."
                       class="w-full pl-12 pr-12 py-3.5 text-base border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                <button @click="close()" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition" aria-label="Fermer">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Loader --}}
            <div x-show="loading" class="text-center py-6">
                <svg class="animate-spin h-5 w-5 mx-auto text-brand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>

            {{-- Résultats --}}
            <div x-show="!loading && results.length > 0" class="mt-4 max-h-[60vh] overflow-y-auto divide-y divide-gray-100">
                <template x-for="item in results" :key="item.url">
                    <a :href="item.url" @click="close()" class="flex items-center gap-4 py-3 px-2 hover:bg-brand-50 rounded-lg transition group">
                        <div class="w-12 h-12 rounded-lg flex-shrink-0 overflow-hidden bg-gray-100">
                            <img x-show="item.image" :src="item.image" :alt="item.name" class="w-full h-full object-cover">
                            <div x-show="!item.image" class="w-full h-full flex items-center justify-center text-gray-300">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 group-hover:text-brand-700 transition truncate" x-text="item.name"></p>
                            <p class="text-xs text-gray-400" x-text="item.category"></p>
                        </div>
                        <p class="text-sm font-semibold text-brand-700 flex-shrink-0" x-text="item.price"></p>
                    </a>
                </template>
            </div>

            {{-- Aucun résultat --}}
            <div x-show="!loading && query.length >= 2 && results.length === 0" class="text-center py-8">
                <p class="text-sm text-gray-500">Aucun produit trouvé pour "<span x-text="query"></span>"</p>
            </div>

            {{-- Lien vers la boutique avec recherche complète --}}
            <div x-show="query.length >= 2" class="mt-4 text-center">
                <a :href="'{{ route('shop.index') }}?q=' + encodeURIComponent(query)" @click="close()"
                   class="text-sm font-medium text-brand-600 hover:text-brand-800 transition">
                    Voir tous les résultats pour "<span x-text="query"></span>"
                </a>
            </div>
        </div>
    </div>
</div>
