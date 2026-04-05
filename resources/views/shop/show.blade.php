<x-layouts.app :title="$product->meta_title ?: $product->name" :meta-description="$product->meta_description ?: $product->short_description" og-type="product">

@if($product->featuredImage?->url)
@push('head')
<link rel="preload" as="image" href="{{ $product->featuredImage->url }}">
@endpush
@endif

{{-- Schema.org Product + BreadcrumbList --}}
@php
$productSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product->name,
    'description' => strip_tags($product->short_description ?? $product->description ?? ''),
    'image' => $product->featuredImage?->url ? [url($product->featuredImage->url)] : [],
    'sku' => $product->sku ?: 'prod-' . $product->id,
    'brand' => [
        '@type' => 'Brand',
        'name' => "Atelier d'Aubin",
    ],
    'offers' => [
        '@type' => 'Offer',
        'url' => url()->current(),
        'priceCurrency' => 'EUR',
        'price' => number_format(
            $product->effective_price > 0
                ? $product->effective_price
                : ($product->category && in_array($product->category->slug, ['lettres-en-bois', 'lettre-en-bois-3d'])
                    ? ($product->getAllAddonGroups()->flatMap->addons->where('label', 'Taille des lettres')->flatMap(fn($a) => collect($a->options)->pluck('price'))->filter()->min() ?: 0)
                    : 0),
            2, '.', ''),
        'availability' => !$product->isInStock()
            ? 'https://schema.org/OutOfStock'
            : 'https://schema.org/InStock',
    ],
];
if ($reviews->count() > 0) {
    $avgRating = round($reviews->avg('rating'), 1);
    $productSchema['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => $avgRating,
        'reviewCount' => $reviews->count(),
        'bestRating' => 5,
        'worstRating' => 1,
    ];
    $productSchema['review'] = $reviews->map(fn($r) => [
        '@type'         => 'Review',
        'reviewBody'    => $r->content,
        'datePublished' => $r->created_at->toDateString(),
        'author'        => ['@type' => 'Person', 'name' => $r->author_name],
        'reviewRating'  => [
            '@type'       => 'Rating',
            'ratingValue' => $r->rating,
            'bestRating'  => 5,
            'worstRating' => 1,
        ],
    ])->values()->all();
}
$productJsonLd = json_encode($productSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$breadcrumbItems = [
    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Boutique', 'item' => route('shop.index')],
];
$pos = 2;
if ($product->category?->parent) {
    $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => $pos++, 'name' => $product->category->parent->name, 'item' => $product->category->parent->url()];
}
if ($product->category) {
    $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => $pos++, 'name' => $product->category->name, 'item' => $product->category->url()];
}
$breadcrumbItems[] = ['@type' => 'ListItem', 'position' => $pos, 'name' => $product->name];
$breadcrumbJsonLd = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => $breadcrumbItems,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
@endphp
<script type="application/ld+json">{!! $productJsonLd !!}</script>
<script type="application/ld+json">{!! $breadcrumbJsonLd !!}</script>

@if(!$product->is_active && auth()->user()?->is_admin)
    <div class="bg-amber-500 text-white text-sm font-semibold text-center py-2.5" role="status">
        Ce produit est masque — visible uniquement par les administrateurs
    </div>
@endif

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Fil d'ariane --}}
    <nav class="text-xs mb-8 flex items-center gap-2 text-brand-500">
        <a href="{{ route('shop.index') }}" class="hover:underline">Boutique</a>
        @if($product->category)
            @if($product->category->parent)
                <span>/</span>
                <a href="{{ $product->category->parent->url() }}" class="hover:underline">{{ $product->category->parent->name }}</a>
            @endif
            <span>/</span>
            <a href="{{ $product->category->url() }}" class="hover:underline">{{ $product->category->name }}</a>
        @endif
        <span>/</span>
        <span class="text-brand-700">{{ $product->name }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16">

        {{-- Galerie --}}
        @php
            $allImages = collect();
            if ($product->featuredImage) $allImages->push($product->featuredImage);
            $allImages = $allImages->merge($galleryImages);
        @endphp
        <div x-data="{ active: 0, lightbox: false, images: {{ $allImages->pluck('url')->toJson() }}, init() { this.$watch('lightbox', v => this.$dispatch(v ? 'lightbox-opened' : 'lightbox-closed')) } }"
             class="lg:sticky lg:top-6 lg:self-start">
            {{-- Image principale --}}
            <div class="aspect-square rounded-3xl overflow-hidden mb-3 relative group bg-brand-50">
                @if($allImages->isNotEmpty())
                    @foreach($allImages as $i => $img)
                        <img src="{{ $img->url }}"
                             alt="{{ $img->alt ?: $product->name }}"
                             @if($img->width && $img->height) width="{{ $img->width }}" height="{{ $img->height }}" @endif
                             x-show="active === {{ $i }}"
                             @if($i === 0) fetchpriority="high" style="display: block" @else loading="lazy" @endif
                             class="w-full h-full object-cover cursor-zoom-in"
                             @click="lightbox = true">
                    @endforeach
                    <div class="absolute bottom-3 right-3 rounded-full p-1.5 opacity-0 group-hover:opacity-100 transition pointer-events-none"
                         style="background-color: rgba(0,0,0,0.4);">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16zm3-8H8m3-3v6"/>
                        </svg>
                    </div>
                @else
                    <div class="w-full h-full flex items-center justify-center text-brand-200">
                        <svg class="w-24 h-24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                @endif
            </div>

            {{-- Miniatures --}}
            @if($allImages->count() > 1)
                <div class="flex gap-2 overflow-x-auto pb-2 lg:flex-wrap lg:overflow-visible lg:pb-0 scrollbar-hide">
                    @foreach($allImages as $i => $img)
                        <button @click="active = {{ $i }}"
                                class="w-16 h-16 lg:w-20 lg:h-20 rounded-lg overflow-hidden border-2 transition shrink-0"
                                :class="active === {{ $i }} ? 'border-brand-600' : 'border-transparent hover:border-brand-300'">
                            <img src="{{ $img->url }}" alt="{{ $img->alt ?: $product->name }}"
                                 loading="lazy" class="w-full h-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Lightbox (teleported to body to escape sticky stacking context) --}}
            @if($allImages->isNotEmpty())
                <template x-teleport="body">
                    <div x-show="lightbox" x-cloak
                         class="fixed inset-0 z-[100] flex items-center justify-center p-4"
                         style="background-color: rgba(0,0,0,0.88);"
                         @click.self="lightbox = false"
                         @keydown.escape.window="lightbox = false"
                         @keydown.left.window="if(lightbox) active = (active - 1 + images.length) % images.length"
                         @keydown.right.window="if(lightbox) active = (active + 1) % images.length">
                        <button @click="lightbox = false"
                                class="absolute top-4 right-5 text-white opacity-60 hover:opacity-100 transition text-4xl leading-none font-light z-10"
                                aria-label="Fermer">&times;</button>
                        {{-- Fleche gauche --}}
                        <button @click="active = (active - 1 + images.length) % images.length"
                                class="absolute left-4 sm:left-8 text-white opacity-60 hover:opacity-100 transition z-10"
                                aria-label="Precedente">
                            <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <img :src="images[active]"
                             alt="{{ $product->name }}"
                             class="max-h-[90vh] max-w-[90vw] object-contain rounded-2xl shadow-2xl">
                        {{-- Fleche droite --}}
                        <button @click="active = (active + 1) % images.length"
                                class="absolute right-4 sm:right-8 text-white opacity-60 hover:opacity-100 transition z-10"
                                aria-label="Suivante">
                            <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                        {{-- Compteur --}}
                        <span class="absolute bottom-4 text-white text-sm opacity-60" x-text="(active + 1) + ' / ' + images.length"></span>
                    </div>
                </template>
            @endif
        </div>

        {{-- Infos produit --}}
        @php $hasSyncQty = $addonGroups->flatMap->addons->contains(fn($a) => $a->sync_qty); @endphp
        <div x-data="{ qty: 1, syncQty: {{ $hasSyncQty ? 'true' : 'false' }} }">
            <p class="text-xs uppercase tracking-widest mb-2 font-medium text-brand-500">
                {{ $product->category?->name }}
            </p>
            <h1 class="text-3xl md:text-4xl font-semibold leading-tight mb-3 text-brand-700" style="font-family: Georgia, serif;">
                {{ $product->name }}
            </h1>

            {{-- Etoiles --}}
            <div class="flex items-center gap-2 mb-5">
                @if($reviews->isNotEmpty())
                    @php $avgRating = round($reviews->avg('rating'), 1); @endphp
                    <div class="flex items-center gap-0.5">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="w-4 h-4" fill="{{ $i <= round($avgRating) ? '#f59e0b' : '#e5e7eb' }}" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>
                    <a href="#avis" class="text-xs hover:underline text-brand-500">{{ $reviews->count() }} avis</a>
                @else
                    <div class="flex items-center gap-0.5">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="w-4 h-4" fill="#e5e7eb" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>
                    <a href="#avis" class="text-xs hover:underline text-brand-500">Donner mon avis</a>
                @endif
            </div>

            {{-- Prix --}}
            <div class="flex items-baseline gap-3 mb-6">
                @if($product->price == 0 && $product->category && in_array($product->category->slug, ['lettres-en-bois', 'lettre-en-bois-3d']))
                    @php
                        $minLetterPrice = $addonGroups->flatMap->addons
                            ->where('label', 'Taille des lettres')
                            ->flatMap(fn($a) => collect($a->options)->pluck('price'))
                            ->filter()->min() ?: 0;
                    @endphp
                    <span class="text-3xl font-bold text-brand-700">A partir de {{ number_format($minLetterPrice, 2, ',', ' ') }} €</span>
                    <span class="text-base text-brand-400">/ lettre</span>
                @elseif($product->sale_price)
                    <span class="text-3xl font-bold text-brand-700">{{ number_format($product->sale_price, 2, ',', ' ') }} €</span>
                    <span class="text-base line-through text-brand-400">{{ number_format($product->price, 2, ',', ' ') }} €</span>
                @else
                    <span class="text-3xl font-bold text-brand-700">{{ number_format($product->price, 2, ',', ' ') }} €</span>
                @endif
            </div>

            <div style="width: 3rem; height: 2px; background-color: var(--color-brand-200); margin-bottom: 1.5rem;"></div>

            {{-- Description courte --}}
            @if($product->short_description)
                <div class="mb-6 leading-relaxed text-gray-700" style="font-size: 0.95rem;">
                    {!! $product->short_description !!}
                </div>
            @endif

            {{-- Stock epuise --}}
            @if(!$product->isInStock())
                <div class="mb-6 rounded-xl p-4 bg-red-50 border border-red-200">
                    <p class="text-sm font-medium text-red-600">Produit epuise</p>
                </div>
            @endif

            {{-- Formulaire ajout panier --}}
            <form action="{{ route('cart.add') }}" method="POST" class="space-y-4"
                  @submit="if (typeof gtag !== 'undefined') gtag('event', 'add_to_cart', {
                      currency: 'EUR',
                      value: {{ $product->effective_price }} * qty,
                      items: [{
                          item_id: '{{ $product->sku ?: $product->id }}',
                          item_name: '{{ addslashes($product->name) }}',
                          item_category: '{{ addslashes($product->category?->name ?? '') }}',
                          price: {{ $product->effective_price }},
                          quantity: qty
                      }]
                  })">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">

                {{-- Addons --}}
                @if($addonGroups->isNotEmpty())
                    @php
                        $allAddons = $addonGroups->flatMap->addons;
                        $hasSyncQty = $allAddons->contains(fn($a) => $a->sync_qty);
                        $prenomAddon = $allAddons->first(fn($a) => $a->label === 'Prénom' && $a->type === 'text');
                        $prenomMotAddon = $allAddons->first(fn($a) => $a->label === 'Prénom ou mot' && $a->type === 'text');
                        $typoAddon = $allAddons->first(fn($a) => $a->label === 'Typographie' && $a->type === 'select');
                        $tailleAddon = $allAddons->first(fn($a) => $a->label === 'Taille des lettres' && $a->type === 'select');
                        $prixLettresAddon = $allAddons->first(fn($a) => $a->label === 'Prix des lettres' && $a->type === 'select');
                        $nbLettresAddon = $allAddons->first(fn($a) => $a->label === 'Nombre de lettres' && $a->type === 'select');
                    @endphp
                    <div class="space-y-6">
                        @foreach($addonGroups as $group)
                            @if($group->description)
                                <p class="text-xs text-brand-500 mb-3">{{ $group->description }}</p>
                            @endif
                            <div class="space-y-3">
                                @foreach($group->addons as $addon)
                                    <x-product-addon :addon="$addon"/>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    @if($prenomAddon && $typoAddon)
                        <x-typo-preview :prenom-addon-id="$prenomAddon->id" :typo-addon-id="$typoAddon->id" />
                    @endif

                    @if($prenomMotAddon && $tailleAddon && $prixLettresAddon)
                        <x-letter-price :prenom-addon-id="$prenomMotAddon->id" :taille-addon-id="$tailleAddon->id" :prix-addon-id="$prixLettresAddon->id" />
                    @endif

                    @if($prenomMotAddon && $nbLettresAddon && !$tailleAddon)
                        <x-letter-count-sync :prenom-addon-id="$prenomMotAddon->id" :nb-lettres-addon-id="$nbLettresAddon->id" />
                    @endif
                @endif

                {{-- Quantite + bouton --}}
                <div class="flex items-center gap-4 pt-2">
                    <div class="flex items-center rounded-xl overflow-hidden border border-brand-200" :class="syncQty && 'opacity-50'">
                        <button type="button"
                                @click="qty = Math.max(1, qty - 1)"
                                :disabled="syncQty"
                                aria-label="Diminuer la quantite"
                                class="px-3.5 py-2.5 transition hover:bg-brand-50 text-brand-700">-</button>
                        <input type="number" name="quantity" x-model="qty"
                               min="1" max="99" aria-label="Quantite"
                               :readonly="syncQty"
                               class="w-12 text-center py-2.5 border-0 text-sm focus:outline-none text-brand-700 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                        <button type="button"
                                @click="qty = Math.min(99, qty + 1)"
                                :disabled="syncQty"
                                aria-label="Augmenter la quantite"
                                class="px-3.5 py-2.5 transition hover:bg-brand-50 text-brand-700">+</button>
                    </div>
                    <button type="submit"
                            {{ !$product->isInStock() ? 'disabled' : '' }}
                            class="flex-1 text-white py-3 px-6 rounded-xl font-semibold text-sm transition disabled:cursor-not-allowed hover:opacity-90 {{ $product->isInStock() ? 'bg-brand-600' : 'bg-gray-400' }}">
                        {{ $product->isInStock() ? 'Ajouter au panier' : 'Produit epuise' }}
                    </button>
                </div>
            </form>

            {{-- Informations livraison --}}
            @if($product->isInStock())
                <div class="mt-5 pt-5 space-y-2.5 border-t border-gray-200">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                        <span class="text-xs text-gray-700">Livraison en point relais — 5,00 €</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span class="text-xs text-gray-700">Livraison a domicile (Colissimo) — 7,90 €</span>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>

{{-- Description complete --}}
@if($product->description)
    <section class="py-16 bg-brand-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-xs uppercase tracking-widest font-medium mb-2 text-brand-500">Tout savoir</p>
            <h2 class="text-2xl font-semibold mb-6 text-brand-700" style="font-family: Georgia, serif;">
                Description
            </h2>
            <div class="product-description max-w-3xl">
                {!! $product->description !!}
            </div>
        </div>
    </section>
@endif

{{-- Avis clients --}}
<section id="avis" class="py-16 bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        @php
            $starPath = 'M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z';
        @endphp

        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-6 mb-10">
            <div>
                <p class="text-xs uppercase tracking-widest font-medium mb-2 text-brand-500">Retours d'experience</p>
                <h2 class="text-2xl font-semibold text-brand-700" style="font-family: Georgia, serif;">
                    Avis clients
                    @if($reviews->isNotEmpty())
                        <span class="text-base font-normal text-brand-500">({{ $reviews->count() }})</span>
                    @endif
                </h2>
            </div>

            @if($reviews->isNotEmpty())
                @php
                    $avgRating = round($reviews->avg('rating'), 1);
                    $distrib = [];
                    for ($s = 5; $s >= 1; $s--) {
                        $distrib[$s] = $reviews->where('rating', $s)->count();
                    }
                @endphp
                <div class="flex items-center gap-6 shrink-0">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-brand-700">{{ number_format($avgRating, 1, ',', '') }}</div>
                        <div class="flex justify-center gap-0.5 my-1">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-4 h-4" fill="{{ $i <= round($avgRating) ? '#f59e0b' : '#e5e7eb' }}" viewBox="0 0 20 20"><path d="{{ $starPath }}"/></svg>
                            @endfor
                        </div>
                        <div class="text-xs text-gray-400">sur 5</div>
                    </div>
                    <div class="space-y-1 min-w-[140px]">
                        @foreach($distrib as $stars => $count)
                            @php $pct = $reviews->count() > 0 ? round($count / $reviews->count() * 100) : 0; @endphp
                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                <span class="w-3 text-right">{{ $stars }}</span>
                                <svg class="w-3 h-3 shrink-0" fill="#f59e0b" viewBox="0 0 20 20"><path d="{{ $starPath }}"/></svg>
                                <div class="flex-1 h-1.5 rounded-full overflow-hidden bg-gray-200">
                                    <div class="h-full rounded-full bg-amber-400" style="width: {{ $pct }}%;"></div>
                                </div>
                                <span class="w-7 text-right">{{ $pct }}%</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Liste des avis --}}
        @if($reviews->isNotEmpty())
            <div class="space-y-6 mb-12">
                @foreach($reviews as $review)
                    <div class="border border-gray-100 rounded-xl p-5">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <div class="flex gap-0.5">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-4 h-4" fill="{{ $i <= $review->rating ? '#f59e0b' : '#e5e7eb' }}" viewBox="0 0 20 20"><path d="{{ $starPath }}"/></svg>
                                    @endfor
                                </div>
                                <span class="text-sm font-medium text-gray-800">{{ $review->author_name }}</span>
                            </div>
                            <span class="text-xs text-gray-400">{{ $review->created_at->format('d/m/Y') }}</span>
                        </div>
                        <p class="text-sm text-gray-600 leading-relaxed">{{ $review->content }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Formulaire d'avis --}}
        <div class="max-w-xl">
            <h3 class="text-lg font-semibold text-brand-700 mb-4" style="font-family: Georgia, serif;">Laisser un avis</h3>

            @if(session('review_success'))
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg px-4 py-3 mb-4">
                    {{ session('review_success') }}
                </div>
            @endif
            @if(session('review_error'))
                <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-lg px-4 py-3 mb-4">
                    {{ session('review_error') }}
                </div>
            @endif

            <form action="{{ route('shop.review.store', $product) }}" method="POST" class="space-y-4" data-turbo="false">
                @csrf
                {{-- Honeypot anti-spam --}}
                <div aria-hidden="true" style="position:absolute;left:-9999px;">
                    <label for="review_website">Ne pas remplir</label>
                    <input type="text" name="website" id="review_website" tabindex="-1" autocomplete="off">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="author_name" class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                        <input type="text" id="author_name" name="author_name" required maxlength="100"
                               value="{{ old('author_name', auth()->user()?->first_name) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                        @error('author_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="author_email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="author_email" name="author_email" required
                               value="{{ old('author_email', auth()->user()?->email) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                        @error('author_email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                    <div class="flex gap-1" x-data="{ rating: 5 }">
                        @for($i = 1; $i <= 5; $i++)
                            <label class="cursor-pointer">
                                <input type="radio" name="rating" value="{{ $i }}" class="sr-only" x-model="rating" {{ $i === 5 ? 'checked' : '' }}>
                                <svg class="w-7 h-7 transition" :fill="rating >= {{ $i }} ? '#f59e0b' : '#e5e7eb'" viewBox="0 0 20 20">
                                    <path d="{{ $starPath }}"/>
                                </svg>
                            </label>
                        @endfor
                    </div>
                    @error('rating') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="review_content" class="block text-sm font-medium text-gray-700 mb-1">Votre avis</label>
                    <textarea id="review_content" name="content" rows="4" required maxlength="2000"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">{{ old('content') }}</textarea>
                    @error('content') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <button type="submit"
                        class="bg-brand-600 text-white px-6 py-2.5 rounded-lg font-medium text-sm hover:opacity-90 transition">
                    Envoyer mon avis
                </button>
            </form>
        </div>

    </div>
</section>

{{-- Produits similaires --}}
@if($related->isNotEmpty())
<section class="py-16 bg-brand-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-semibold mb-8 text-brand-700" style="font-family: Georgia, serif;">
            Produits similaires
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
            @foreach($related as $relProduct)
                <x-product-card :product="$relProduct"/>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- GA4 view_item --}}
@if(config('tracking.google_analytics_id'))
<script>
    gtag('event', 'view_item', {
        currency: 'EUR',
        value: {{ $product->effective_price }},
        items: [{
            item_id: '{{ $product->sku ?: $product->id }}',
            item_name: '{{ addslashes($product->name) }}',
            item_category: '{{ addslashes($product->category?->name ?? '') }}',
            price: {{ $product->effective_price }},
            quantity: 1
        }]
    });
</script>
@endif

</x-layouts.app>
