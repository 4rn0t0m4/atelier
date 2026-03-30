@props(['prenomAddonId', 'tailleAddonId', 'prixAddonId'])

<script>
window.letterPrice = function(prenomId, tailleId, prixId) {
    return {
        init() {
            const prenomInput = document.querySelector(`[name="addons[${prenomId}][value]"]`);
            const tailleSelect = document.querySelector(`[name="addons[${tailleId}][value]"]`);
            const prixSelect = document.querySelector(`[name="addons[${prixId}][value]"]`);

            if (!prenomInput || !tailleSelect || !prixSelect) return;

            // Cacher le select prix des lettres
            const prixContainer = prixSelect.closest('.space-y-1');
            if (prixContainer) prixContainer.style.display = 'none';

            const updatePrix = () => {
                const str = prenomInput.value.replace(/\s/g, '');
                prenomInput.value = str;
                const nb = str.length;
                if (nb === 0) { prixSelect.value = ''; prixSelect.dispatchEvent(new Event('change')); return; }

                // Extraire la taille du label sélectionné (ex: "8 cm (5€ par lettre suppl.)" → "8")
                const tailleVal = tailleSelect.value;
                const tailleMatch = tailleVal.match(/^(\d+)\s*cm/);
                if (!tailleMatch) return;
                const taille = tailleMatch[1];

                // Chercher l'option correspondante
                const label = nb === 1
                    ? `1 lettre de ${taille}cm`
                    : `${nb} lettres de ${taille}cm`;

                const options = prixSelect.querySelectorAll('option');
                let found = false;
                for (const opt of options) {
                    if (opt.value === label || opt.textContent.trim().startsWith(label)) {
                        prixSelect.value = opt.value;
                        found = true;
                        break;
                    }
                }
                if (!found) {
                    // Prendre la dernière option disponible pour cette taille
                    let lastMatch = null;
                    for (const opt of options) {
                        if (opt.value.includes(`de ${taille}cm`) || opt.textContent.includes(`de ${taille}cm`)) {
                            lastMatch = opt;
                        }
                    }
                    if (lastMatch) prixSelect.value = lastMatch.value;
                }
                prixSelect.dispatchEvent(new Event('change'));
            };

            prenomInput.addEventListener('input', updatePrix);
            tailleSelect.addEventListener('change', updatePrix);
        }
    };
};
</script>

<div x-data="letterPrice({{ $prenomAddonId }}, {{ $tailleAddonId }}, {{ $prixAddonId }})" class="hidden"></div>
