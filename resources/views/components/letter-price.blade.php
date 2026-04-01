@props(['prenomAddonId', 'tailleAddonId', 'prixAddonId'])

<div x-data="letterPrice({{ $prenomAddonId }}, {{ $tailleAddonId }}, {{ $prixAddonId }})"
     class="rounded-xl border border-brand-200 bg-brand-50 p-4 mt-2">

    {{-- État vide --}}
    <p x-show="nbLettres === 0" class="text-sm text-brand-400">
        Saisissez un prenom pour voir le prix
    </p>

    {{-- Détail du prix --}}
    <div x-show="nbLettres > 0" x-cloak class="space-y-1.5">
        {{-- Ligne lettres --}}
        <div class="flex items-center justify-between text-sm text-brand-600">
            <span>
                <span x-text="nbLettres"></span> lettre<span x-show="nbLettres > 1">s</span>
                &times; <span x-text="fmt(prixParLettre)"></span> €
            </span>
            <span x-text="fmt(nbLettres * prixParLettre) + ' €'"></span>
        </div>

        {{-- Lignes options supplémentaires --}}
        <template x-for="opt in extras" :key="opt.label">
            <div class="flex items-center justify-between text-sm text-brand-500">
                <span>+ <span x-text="opt.label"></span></span>
                <span x-text="fmt(opt.type === 'quantity_based' ? opt.price * nbLettres : opt.price) + ' €'"></span>
            </div>
        </template>

        {{-- Total --}}
        <div class="flex items-center justify-between pt-1.5 border-t border-brand-200">
            <span class="text-sm font-semibold text-brand-700">Total estimé</span>
            <span class="text-lg font-bold text-brand-700" x-text="fmt(total) + ' €'"></span>
        </div>
    </div>
</div>

<script>
window.letterPrice = function(prenomId, tailleId, prixId) {
    return {
        nbLettres: 0,
        prixParLettre: 0,
        extras: [],
        total: 0,

        fmt(n) { return n.toFixed(2).replace('.', ','); },

        init() {
            const form = this.$el.closest('form');
            const prenomInput = form.querySelector(`[name="addons[${prenomId}][value]"]`);
            const tailleSelect = form.querySelector(`[name="addons[${tailleId}][value]"]`);
            const prixSelect = form.querySelector(`[name="addons[${prixId}][value]"]`);
            const qtyInput = form.querySelector('input[name="quantity"]');

            if (!prenomInput || !tailleSelect) return;

            const getSelectedPrice = (sel) => {
                const opt = sel.options[sel.selectedIndex];
                return opt && opt.dataset.price ? parseFloat(opt.dataset.price) : 0;
            };
            const getSelectedPriceType = (sel) => {
                const opt = sel.options[sel.selectedIndex];
                return opt && opt.dataset.priceType ? opt.dataset.priceType : 'flat_fee';
            };

            // Cacher le select prix des lettres (plus utilisé, le prix vient de Taille)
            if (prixSelect) {
                const prixContainer = prixSelect.closest('.space-y-1');
                if (prixContainer) prixContainer.style.display = 'none';
                prixSelect.value = '';
                prixSelect.removeAttribute('required');
            }

            // Cacher les contrôles de quantité (gérée automatiquement par le nombre de lettres)
            if (qtyInput) {
                const qtyContainer = qtyInput.closest('.flex.items-center.rounded-xl');
                if (qtyContainer) qtyContainer.style.display = 'none';
            }

            // Trouver les autres selects addon avec prix (hors taille et prix lettres)
            const otherSelects = [...form.querySelectorAll('select[name^="addons["]')].filter(sel => {
                return sel !== tailleSelect && sel !== prixSelect;
            });

            const update = () => {
                const str = prenomInput.value.replace(/\s/g, '');
                prenomInput.value = str;
                this.nbLettres = str.length;

                // Prix par lettre selon la taille sélectionnée
                this.prixParLettre = getSelectedPrice(tailleSelect);

                // Prix des lettres
                let t = this.nbLettres * this.prixParLettre;

                // Collecter les extras (autres addons avec prix)
                this.extras = [];
                otherSelects.forEach(sel => {
                    const price = getSelectedPrice(sel);
                    if (price > 0) {
                        const type = getSelectedPriceType(sel);
                        const label = sel.closest('.space-y-1')?.querySelector('label')?.textContent?.trim() || 'Option';
                        this.extras.push({ label, price, type });
                        t += type === 'quantity_based' ? price * this.nbLettres : price;
                    }
                });

                this.total = t;

                // Synchroniser la quantité avec le nombre de lettres
                if (qtyInput) {
                    const val = Math.max(1, this.nbLettres);
                    qtyInput.value = val;
                    qtyInput.dispatchEvent(new Event('input', { bubbles: true }));
                    if (qtyInput._x_model) qtyInput._x_model.set(val);
                }
            };

            prenomInput.addEventListener('input', update);
            tailleSelect.addEventListener('change', update);
            otherSelects.forEach(sel => sel.addEventListener('change', update));
        }
    };
};
</script>
