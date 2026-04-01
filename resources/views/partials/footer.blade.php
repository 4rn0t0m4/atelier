<footer class="border-t border-brand-200 bg-brand-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-sm text-brand-700">

            <div class="md:col-span-1">
                <p class="font-semibold text-base mb-2" style="font-family: Georgia, serif;">
                    Atelier d'Aubin
                </p>
                <p class="text-brand-500 italic">Objets en bois faits main</p>
                <div class="mt-4 space-y-1 text-xs text-brand-500">
                    <p class="font-medium text-brand-700">contact@atelier-aubin.fr</p>
                </div>
            </div>

            <div>
                <p class="font-semibold mb-3">Boutique</p>
                <ul class="space-y-1.5 text-brand-500">
                    <li><a href="{{ route('shop.index') }}" class="hover:opacity-70 transition-opacity">Tous les produits</a></li>
                </ul>
            </div>

            <div>
                <p class="font-semibold mb-3">Mon compte</p>
                <ul class="space-y-1.5 text-brand-500">
                    <li><a href="{{ route('cart.index') }}" class="hover:opacity-70 transition-opacity">Mon panier</a></li>
                    @auth
                        <li><a href="{{ url('/mon-compte') }}" class="hover:opacity-70 transition-opacity">Mon compte</a></li>
                    @else
                        <li><a href="{{ url('/connexion') }}" class="hover:opacity-70 transition-opacity">Connexion</a></li>
                    @endauth
                </ul>
            </div>

            <div>
                <p class="font-semibold mb-3">Informations</p>
                <ul class="space-y-1.5 text-brand-500">
                    <li><a href="{{ route('page.show', 'mentions-legales') }}" class="hover:opacity-70 transition-opacity">Mentions legales</a></li>
                    <li><a href="{{ route('page.show', 'politique-de-confidentialite') }}" class="hover:opacity-70 transition-opacity">Confidentialite</a></li>
                    <li><a href="{{ route('page.show', 'cgv') }}" class="hover:opacity-70 transition-opacity">CGV</a></li>
                    <li><a href="{{ route('contact.show') }}" class="hover:opacity-70 transition-opacity">Contact</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-10 pt-6 border-t border-brand-200 text-center text-xs text-brand-400">
            &copy; {{ date('Y') }} Atelier d'Aubin — Tous droits reserves
        </div>
    </div>
</footer>
