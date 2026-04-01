@props(['prenomAddonId', 'nbLettresAddonId'])

<script>
window.letterCountSync = function(prenomId, nbLettresId) {
    return {
        init() {
            const form = this.$el.closest('form');
            const prenomInput = form.querySelector(`[name="addons[${prenomId}][value]"]`);
            const nbSelect = form.querySelector(`[name="addons[${nbLettresId}][value]"]`);

            if (!prenomInput || !nbSelect) return;

            // Construire le mapping nombre → index d'option
            const optionsByCount = {};
            nbSelect.querySelectorAll('option').forEach((opt, i) => {
                const match = opt.value.match(/^(\d+)/);
                if (match) optionsByCount[parseInt(match[1])] = opt.value;
            });

            const maxLetters = Math.max(...Object.keys(optionsByCount).map(Number));

            prenomInput.addEventListener('input', () => {
                const nb = prenomInput.value.replace(/\s/g, '').length;
                const clamped = Math.min(Math.max(1, nb), maxLetters);

                if (nb > 0 && optionsByCount[clamped]) {
                    nbSelect.value = optionsByCount[clamped];
                    nbSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }
    };
};
</script>

<div x-data="letterCountSync({{ $prenomAddonId }}, {{ $nbLettresAddonId }})" class="hidden"></div>
