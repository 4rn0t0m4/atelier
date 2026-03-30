@props(['prenomAddonId', 'typoAddonId'])

<style>
@font-face { font-family: 'Brilliant Summer'; src: url('/fonts/typo/brilliant-summer.otf') format('opentype'); font-display: swap; }
@font-face { font-family: 'Autography'; src: url('/fonts/typo/autography.otf') format('opentype'); font-display: swap; }
@font-face { font-family: 'Pacifico'; src: url('/fonts/typo/pacifico.ttf') format('truetype'); font-display: swap; }
@font-face { font-family: 'Birds of Paradise'; src: url('/fonts/typo/birds-of-paradise.ttf') format('truetype'); font-display: swap; }
@font-face { font-family: 'Ms Madi'; src: url('/fonts/typo/ms-madi.ttf') format('truetype'); font-display: swap; }
@font-face { font-family: 'Madina'; src: url('/fonts/typo/madina.otf') format('opentype'); font-display: swap; }
@font-face { font-family: 'Updock'; src: url('/fonts/typo/updock.ttf') format('truetype'); font-display: swap; }
@font-face { font-family: 'Brannboll Connect'; src: url('/fonts/typo/brannboll-connect.ttf') format('truetype'); font-display: swap; }
@font-face { font-family: 'Cursive Standard'; src: url('/fonts/typo/cursive-standard.ttf') format('truetype'); font-display: swap; }
@font-face { font-family: 'Angelisa'; src: url('/fonts/typo/angelisa.otf') format('opentype'); font-display: swap; }
@font-face { font-family: 'Autumn November'; src: url('/fonts/typo/autumn-november.ttf') format('truetype'); font-display: swap; }
@font-face { font-family: 'Brayden Script'; src: url('/fonts/typo/brayden-script.otf') format('opentype'); font-display: swap; }
@font-face { font-family: 'Master of Break'; src: url('/fonts/typo/master-of-break.otf') format('opentype'); font-display: swap; }
@font-face { font-family: 'Always In My Heart'; src: url('/fonts/typo/always-in-my-heart.ttf') format('truetype'); font-display: swap; }
@font-face { font-family: 'Dearheart'; src: url('/fonts/typo/dearheart.otf') format('opentype'); font-display: swap; }
@font-face { font-family: 'Jenny Lovely'; src: url('/fonts/typo/jenny-lovely.ttf') format('truetype'); font-display: swap; }
</style>

<script>
window.typoPreview = function(prenomId, typoId) {
    const fontMap = {
        'Typographie 1':  'Brilliant Summer',
        'Typographie 2':  'Autography',
        'Typographie 3':  'Pacifico',
        'Typographie 4':  'Birds of Paradise',
        'Typographie 5':  'Ms Madi',
        'Typographie 6':  'Madina',
        'Typographie 7':  'Updock',
        'Typographie 8':  'Brannboll Connect',
        'Typographie 9':  'Cursive Standard',
        'Typographie 10': 'Angelisa',
        'Typographie 11': 'Autumn November',
        'Typographie 12': 'Brayden Script',
        'Typographie 13': 'Master of Break',
        'Typographie 14': 'Always In My Heart',
        'Typographie 15': 'Dearheart',
        'Typographie 16': 'Jenny Lovely',
    };

    return {
        prenom: '',
        font: '',

        init() {
            const prenomInput = document.querySelector(`[name="addons[${prenomId}][value]"]`);
            const typoSelect = document.querySelector(`[name="addons[${typoId}][value]"]`);

            if (prenomInput) {
                prenomInput.addEventListener('input', () => {
                    this.prenom = prenomInput.value.replace(/\s/g, '');
                    prenomInput.value = this.prenom;
                });
            }
            if (typoSelect) {
                typoSelect.addEventListener('change', () => {
                    const val = typoSelect.value.replace(/\s*\(.*\)\s*$/, '');
                    this.font = fontMap[val] || '';
                });
            }
        },
    };
};
</script>

<div x-data="typoPreview({{ $prenomAddonId }}, {{ $typoAddonId }})"
     class="mt-3 rounded-lg border border-brand-200 bg-[#faf9f7] p-5 text-center flex items-center justify-center transition-all duration-300"
     style="min-height: 80px;">
    <span x-show="!prenom" class="text-sm italic text-brand-400">
        Saisissez un prenom et choisissez une typographie pour voir l'apercu
    </span>
    <span x-show="prenom && !font" class="text-sm italic text-brand-400">
        Choisissez une typographie pour voir l'apercu
    </span>
    <span x-show="prenom && font" x-text="prenom"
          :style="'font-family: \'' + font + '\', cursive'"
          class="text-[62px] leading-relaxed text-gray-800 break-words transition-[font-family] duration-300">
    </span>
</div>
