@php $group = $addonGroup ?? null; @endphp

<div x-data="addonGroupForm()" class="space-y-6">
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Colonne principale --}}
        <div class="xl:col-span-2 space-y-6">
            {{-- Infos groupe --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-700 uppercase mb-4">Groupe d'options</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom du groupe *</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $group?->name) }}" required
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">Ordre d'affichage</label>
                        <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $group?->sort_order ?? 0) }}" min="0"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                </div>

                <div class="mt-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-gray-400">(affichée au client)</span></label>
                    <input type="text" name="description" id="description" value="{{ old('description', $group?->description) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
            </div>

            {{-- Builder de champs --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm font-semibold text-gray-700 uppercase">Champs <span class="text-gray-400 font-normal" x-text="'(' + addons.length + ')'"></span></h3>
                    <button type="button" @click="addAddon()"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-brand-700 rounded-lg hover:bg-brand-800 transition">
                        + Ajouter un champ
                    </button>
                </div>

                <div class="space-y-4" x-ref="addonList">
                    <template x-for="(addon, addonIndex) in addons" :key="addon._key">
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            {{-- Header du champ (toujours visible) --}}
                            <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 cursor-pointer select-none"
                                 @click="addon._open = !addon._open">
                                <button type="button" @click.stop="moveAddon(addonIndex, -1)" class="text-gray-400 hover:text-gray-600" :class="{'opacity-30': addonIndex === 0}" :disabled="addonIndex === 0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                </button>
                                <button type="button" @click.stop="moveAddon(addonIndex, 1)" class="text-gray-400 hover:text-gray-600" :class="{'opacity-30': addonIndex === addons.length - 1}" :disabled="addonIndex === addons.length - 1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>

                                <span class="flex-1 text-sm font-medium text-gray-700" x-text="addon.label || '(sans nom)'"></span>

                                <span class="text-xs text-gray-400 bg-white px-2 py-0.5 rounded" x-text="typeLabels[addon.type] || addon.type"></span>

                                <template x-if="addon.required">
                                    <span class="text-xs text-red-500 font-medium">Requis</span>
                                </template>

                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="{'rotate-180': addon._open}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>

                                <button type="button" @click.stop="removeAddon(addonIndex)" class="text-red-400 hover:text-red-600 ml-1" title="Supprimer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>

                            {{-- Corps du champ (dépliable) --}}
                            <div x-show="addon._open" x-collapse class="px-4 py-4 space-y-4">
                                <input type="hidden" :name="'addons['+addonIndex+'][id]'" :value="addon.id || ''">

                                {{-- Ligne 1 : Label + Type --}}
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Label *</label>
                                        <input type="text" :name="'addons['+addonIndex+'][label]'" x-model="addon.label" required
                                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Type de champ</label>
                                        <select :name="'addons['+addonIndex+'][type]'" x-model="addon.type"
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                                            <option value="heading">Titre / Séparateur</option>
                                            <option value="text">Texte court</option>
                                            <option value="textarea">Texte long</option>
                                            <option value="select">Liste déroulante</option>
                                            <option value="radio">Boutons radio</option>
                                            <option value="checkbox">Cases à cocher</option>
                                            <option value="file">Fichier</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Ligne 2 : Description + Placeholder --}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                                        <input type="text" :name="'addons['+addonIndex+'][description]'" x-model="addon.description" placeholder="Texte d'aide pour le client"
                                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                                    </div>
                                    <div x-show="['text','textarea'].includes(addon.type)">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Placeholder</label>
                                        <input type="text" :name="'addons['+addonIndex+'][placeholder]'" x-model="addon.placeholder"
                                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                                    </div>
                                </div>

                                {{-- Ligne 3 : Options prix (pour text/textarea/file) --}}
                                <div x-show="!hasOptions(addon.type)" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label class="flex items-center gap-2 text-xs font-medium text-gray-600 mb-1">
                                            <input type="hidden" :name="'addons['+addonIndex+'][adjust_price]'" value="0">
                                            <input type="checkbox" :name="'addons['+addonIndex+'][adjust_price]'" value="1" x-model="addon.adjust_price"
                                                   class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                            Modifier le prix
                                        </label>
                                    </div>
                                    <div x-show="addon.adjust_price">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Montant</label>
                                        <input type="number" :name="'addons['+addonIndex+'][price]'" x-model="addon.price" step="0.01" min="0"
                                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                                    </div>
                                    <div x-show="addon.adjust_price">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Type de prix</label>
                                        <select :name="'addons['+addonIndex+'][price_type]'" x-model="addon.price_type"
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                                            <option value="flat_fee">Fixe (1 fois)</option>
                                            <option value="quantity_based">× quantité</option>
                                            <option value="percentage_based">% du prix</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Ligne 4 : Requis + Min/Max --}}
                                <div class="flex flex-wrap items-center gap-4">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="hidden" :name="'addons['+addonIndex+'][required]'" value="0">
                                        <input type="checkbox" :name="'addons['+addonIndex+'][required]'" value="1" x-model="addon.required"
                                               class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                        Obligatoire
                                    </label>
                                    <div x-show="hasOptions(addon.type)" class="flex items-center gap-2">
                                        <label class="text-xs text-gray-500">Min</label>
                                        <input type="number" :name="'addons['+addonIndex+'][min]'" x-model="addon.min" min="0" class="w-16 border border-gray-300 rounded px-2 py-1 text-sm">
                                        <label class="text-xs text-gray-500">Max</label>
                                        <input type="number" :name="'addons['+addonIndex+'][max]'" x-model="addon.max" min="0" class="w-16 border border-gray-300 rounded px-2 py-1 text-sm">
                                    </div>
                                </div>

                                {{-- Options (select/radio/checkbox) --}}
                                <div x-show="hasOptions(addon.type)">
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="block text-xs font-semibold text-gray-600 uppercase">Options</label>
                                        <button type="button" @click="addOption(addonIndex)"
                                                class="text-xs text-brand-600 hover:text-brand-800 font-medium">+ Ajouter une option</button>
                                    </div>

                                    <div class="space-y-2">
                                        <template x-for="(opt, optIndex) in addon.options" :key="optIndex">
                                            <div class="flex items-center gap-2 bg-gray-50 rounded-lg p-2">
                                                <input type="text" :name="'addons['+addonIndex+'][options]['+optIndex+'][label]'" x-model="opt.label" placeholder="Label de l'option"
                                                       class="flex-1 border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-brand-500 focus:border-brand-500">
                                                <input type="number" :name="'addons['+addonIndex+'][options]['+optIndex+'][price]'" x-model="opt.price" step="0.01" placeholder="Prix"
                                                       class="w-20 border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-brand-500 focus:border-brand-500">
                                                <select :name="'addons['+addonIndex+'][options]['+optIndex+'][price_type]'" x-model="opt.price_type"
                                                        class="w-32 border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-brand-500 focus:border-brand-500">
                                                    <option value="flat_fee">Fixe</option>
                                                    <option value="quantity_based">× qté</option>
                                                    <option value="percentage_based">%</option>
                                                </select>
                                                <button type="button" @click="removeOption(addonIndex, optIndex)" class="text-red-400 hover:text-red-600">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>

                                    <p x-show="addon.options.length === 0" class="text-xs text-gray-400 mt-2 italic">Aucune option. Cliquez sur "+ Ajouter une option".</p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <p x-show="addons.length === 0" class="text-center text-gray-400 text-sm py-8">
                    Aucun champ. Cliquez sur "+ Ajouter un champ" pour commencer.
                </p>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-700 uppercase mb-4">Portée</h3>

                <div class="space-y-3">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="is_global" value="0">
                        <input type="checkbox" name="is_global" value="1" x-model="isGlobal"
                               @checked(old('is_global', $group?->is_global ?? false))
                               class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        Global (appliqué automatiquement)
                    </label>

                    <div x-show="isGlobal" x-collapse class="mt-3">
                        <label class="block text-xs font-medium text-gray-600 mb-2">Restreindre à certaines catégories</label>
                        <div class="max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3 space-y-1">
                            @foreach($categories as $cat)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="restrict_to_categories[]" value="{{ $cat->id }}"
                                           @checked(in_array($cat->id, old('restrict_to_categories', $group?->restrict_to_categories ?? [])))
                                           class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                    {{ $cat->name }}
                                </label>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Vide = tous les produits</p>
                    </div>

                    <div x-show="!isGlobal" class="mt-2">
                        <p class="text-xs text-gray-400">Ce groupe sera assignable manuellement sur chaque produit.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <button type="submit" class="w-full py-2.5 px-4 text-sm font-medium text-white bg-brand-700 rounded-lg hover:bg-brand-800 transition">
                    {{ $group ? 'Mettre à jour' : 'Créer le groupe' }}
                </button>
                <a href="{{ route('admin.addon-groups.index') }}" class="block text-center mt-3 text-sm text-gray-500 hover:text-gray-700">Annuler</a>
            </div>
        </div>
    </div>
</div>

@php
    $addonsJson = $group?->addons->map(fn($a) => [
        '_key' => $a->id,
        '_open' => false,
        'id' => $a->id,
        'label' => $a->label,
        'type' => $a->type,
        'display' => $a->display,
        'price' => $a->price,
        'price_type' => $a->price_type,
        'adjust_price' => $a->adjust_price,
        'required' => $a->required,
        'min' => $a->min,
        'max' => $a->max,
        'description' => $a->description,
        'placeholder' => $a->placeholder,
        'options' => $a->options ?? [],
    ])->values() ?? [];
    $nextKey = ($group?->addons->count() ?? 0) + 1000;
@endphp

@push('scripts')
<script>
function addonGroupForm() {
    return {
        isGlobal: {{ json_encode(old('is_global', $group?->is_global ?? false)) }},
        addons: @json($addonsJson),
        nextKey: {{ $nextKey }},

        typeLabels: {
            text: 'Texte court',
            textarea: 'Texte long',
            select: 'Liste déroulante',
            radio: 'Boutons radio',
            checkbox: 'Cases à cocher',
            file: 'Fichier',
        },

        hasOptions(type) {
            return ['select', 'radio', 'checkbox'].includes(type);
        },

        addAddon() {
            this.addons.push({
                _key: this.nextKey++,
                _open: true,
                id: null,
                label: '',
                type: 'text',
                display: null,
                price: 0,
                price_type: 'flat_fee',
                adjust_price: false,
                required: false,
                min: null,
                max: null,
                description: '',
                placeholder: '',
                options: [],
            });
        },

        removeAddon(index) {
            if (confirm('Supprimer ce champ ?')) {
                this.addons.splice(index, 1);
            }
        },

        moveAddon(index, direction) {
            const newIndex = index + direction;
            if (newIndex < 0 || newIndex >= this.addons.length) return;
            const temp = this.addons[index];
            this.addons.splice(index, 1);
            this.addons.splice(newIndex, 0, temp);
        },

        addOption(addonIndex) {
            this.addons[addonIndex].options.push({
                label: '',
                price: 0,
                price_type: 'flat_fee',
            });
        },

        removeOption(addonIndex, optIndex) {
            this.addons[addonIndex].options.splice(optIndex, 1);
        },
    }
}
</script>
@endpush
