<x-layouts.app title="Objets decoratifs en bois faits main" meta-description="Atelier d'Aubin — Creations artisanales en bois personnalisables. Decorations, cadeaux et objets uniques faits main.">

{{-- Hero --}}
<section class="relative overflow-hidden bg-brand-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-28">
        <div class="max-w-2xl">
            <p class="text-sm uppercase tracking-widest mb-4 font-medium text-brand-500">
                Creations artisanales en bois
            </p>
            <h1 class="text-4xl md:text-5xl font-semibold leading-tight mb-6 text-brand-700" style="font-family: Georgia, serif;">
                Atelier d'Aubin
            </h1>
            <p class="text-lg mb-4 leading-relaxed text-brand-600 italic font-light">
                Objets decoratifs en bois faits main, personnalisables pour toutes les occasions.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 mt-8">
                <a href="{{ route('shop.index') }}"
                   class="inline-block font-semibold px-8 py-3.5 rounded-xl transition text-sm text-white bg-brand-600 hover:opacity-90">
                    Decouvrir la boutique
                </a>
            </div>
        </div>
    </div>
</section>

{{-- Categories --}}
@if($categories->isNotEmpty())
<section class="py-16 bg-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-semibold text-center mb-10 text-brand-700" style="font-family: Georgia, serif;">
            Nos categories
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-5">
            @foreach($categories as $cat)
                <a href="{{ $cat->url() }}"
                   class="group text-center p-6 rounded-2xl border border-brand-200 bg-brand-50 transition hover:shadow-md">
                    @if($cat->featuredImage)
                        <div class="w-full aspect-video rounded-xl overflow-hidden mb-4">
                            <img src="{{ $cat->featuredImage->url }}" alt="{{ $cat->name }}"
                                 loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        </div>
                    @endif
                    <span class="text-sm font-medium text-brand-700">{{ $cat->name }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Produits vedettes --}}
@if($featuredProducts->isNotEmpty())
<section class="py-16 bg-brand-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-semibold text-brand-700" style="font-family: Georgia, serif;">
                Nos produits
            </h2>
            <a href="{{ route('shop.index') }}" class="text-sm font-medium hover:underline text-brand-600">
                Voir toute la boutique →
            </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-5">
            @foreach($featuredProducts as $product)
                <x-product-card :product="$product"/>
            @endforeach
        </div>
    </div>
</section>
@endif

</x-layouts.app>
