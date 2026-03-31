<x-layouts.app title="Page introuvable">

<section class="py-20">
    <div class="max-w-xl mx-auto px-4 text-center">
        <p class="text-8xl font-bold text-brand-200 mb-6">404</p>
        <h1 class="text-2xl font-semibold text-brand-700 mb-4" style="font-family: Georgia, serif;">
            Page introuvable
        </h1>
        <p class="text-gray-500 mb-8 leading-relaxed">
            La page que vous recherchez n'existe pas ou a été déplacée.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('home') }}"
               class="inline-block font-semibold px-6 py-3 rounded-xl text-sm text-white bg-brand-600 hover:opacity-90 transition">
                Retour à l'accueil
            </a>
            <a href="{{ route('shop.index') }}"
               class="inline-block font-semibold px-6 py-3 rounded-xl text-sm border border-brand-600 text-brand-600 hover:bg-brand-50 transition">
                Parcourir la boutique
            </a>
        </div>
    </div>
</section>

</x-layouts.app>
