@php $isEdit = isset($product); @endphp

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    {{-- Left column --}}
    <div class="xl:col-span-2 space-y-6">
        {{-- General info --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 space-y-4">
            <h3 class="text-base font-semibold text-gray-800">Informations generales</h3>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Nom du produit *</label>
                <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500 @error('name') border-red-400 @enderror">
                @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Slug (URL)</label>
                <input type="text" name="slug" value="{{ old('slug', $product->slug ?? '') }}"
                       placeholder="auto-genere si vide"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500 font-mono text-xs">
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Description courte</label>
                <textarea name="short_description" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('short_description', $product->short_description ?? '') }}</textarea>
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Description</label>
                <textarea name="description" rows="6"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('description', $product->description ?? '') }}</textarea>
            </div>
        </div>

        {{-- Pricing --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 space-y-4">
            <h3 class="text-base font-semibold text-gray-800">Prix & stock</h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Prix *</label>
                    <input type="number" name="price" step="0.01" min="0" value="{{ old('price', $product->price ?? '') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500 @error('price') border-red-400 @enderror">
                    @error('price')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Prix promo</label>
                    <input type="number" name="sale_price" step="0.01" min="0" value="{{ old('sale_price', $product->sale_price ?? '') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">SKU</label>
                    <input type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Statut stock</label>
                    <select name="stock_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                        @foreach(['instock' => 'En stock', 'outofstock' => 'Rupture', 'onbackorder' => 'Commande'] as $val => $lbl)
                            <option value="{{ $val }}" {{ old('stock_status', $product->stock_status ?? 'instock') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Quantite en stock</label>
                    <input type="number" name="stock_quantity" min="0" value="{{ old('stock_quantity', $product->stock_quantity ?? '') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Poids (kg)</label>
                    <input type="number" name="weight" step="0.001" min="0" value="{{ old('weight', $product->weight ?? '') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="hidden" name="manage_stock" value="0">
                <input type="checkbox" name="manage_stock" value="1"
                       {{ old('manage_stock', $product->manage_stock ?? false) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                Gerer le stock (decrementer a la vente)
            </label>
        </div>

        {{-- Addon groups --}}
        @php
            $assignedIds = $isEdit ? $product->addonGroups->pluck('id')->toArray() : [];
            $excludedGlobalIds = old('excluded_global_group_ids', $product->excluded_global_group_ids ?? []) ?: [];
            $categoryId = old('category_id', $product->category_id ?? null);

            // Globaux : seulement ceux qui s'appliquent à la catégorie du produit
            $globalGroups = $addonGroups->where('is_global', true)->filter(function ($g) use ($categoryId) {
                if (empty($g->restrict_to_categories)) return true;
                return $categoryId && in_array($categoryId, $g->restrict_to_categories);
            });

            $localGroups = $addonGroups->where('is_global', false);
            $assignedLocal = $localGroups->whereIn('id', $assignedIds);
            $unassignedLocal = $localGroups->whereNotIn('id', $assignedIds);
        @endphp
        <div class="rounded-2xl border border-gray-200 bg-white p-5 space-y-4"
             x-data="{ selected: '' }">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-800">Groupes d'options</h3>
                <a href="{{ route('admin.addon-groups.index') }}" class="text-xs text-brand-600 hover:text-brand-800">Gérer les groupes →</a>
            </div>

            <div class="space-y-2" x-ref="list">
                @foreach($globalGroups as $group)
                    @php $isExcluded = in_array($group->id, $excludedGlobalIds); @endphp
                    <label class="flex items-start gap-3 p-3 rounded-lg border transition cursor-pointer
                        {{ $isExcluded ? 'border-gray-200 bg-gray-50 opacity-60' : 'border-green-100 bg-green-50/50' }}">
                        <input type="checkbox" name="global_groups[]" value="{{ $group->id }}"
                               @checked(! $isExcluded)
                               class="mt-0.5 rounded border-gray-300 text-green-600 focus:ring-green-500">
                        <div>
                            <div class="text-sm font-medium text-gray-700">
                                {{ $group->name }}
                                <span class="text-xs text-green-600 font-normal">(global{{ $group->restrict_to_categories ? ' — ' . count($group->restrict_to_categories) . ' cat.' : '' }})</span>
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                {{ $group->addons->count() }} champ(s) :
                                {{ $group->addons->pluck('label')->implode(', ') }}
                            </div>
                        </div>
                    </label>
                @endforeach

                @foreach($assignedLocal as $group)
                    <div class="flex items-center justify-between p-3 rounded-lg border border-brand-100 bg-brand-50/30" data-addon-group>
                        <input type="hidden" name="addon_groups[]" value="{{ $group->id }}">
                        <div>
                            <div class="text-sm font-medium text-gray-700">{{ $group->name }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                {{ $group->addons->count() }} champ(s) :
                                {{ $group->addons->pluck('label')->implode(', ') }}
                            </div>
                        </div>
                        <button type="button" @click="$el.closest('[data-addon-group]').remove()"
                                class="text-red-400 hover:text-red-600 text-xs ml-2">Retirer</button>
                    </div>
                @endforeach
            </div>

            @if($unassignedLocal->isNotEmpty())
                <div class="flex gap-2">
                    <select x-model="selected" x-ref="groupSelect" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                        <option value="">+ Ajouter un groupe d'options...</option>
                        @foreach($unassignedLocal as $group)
                            <option value="{{ $group->id }}">{{ $group->name }} ({{ $group->addons->count() }} champs)</option>
                        @endforeach
                    </select>
                    <button type="button" x-show="selected" x-cloak
                            @click="if(selected) {
                                let opt = $refs.groupSelect.querySelector('option:checked');
                                let label = opt.textContent.trim();
                                let el = document.createElement('div');
                                el.className = 'flex items-center justify-between p-3 rounded-lg border border-brand-100 bg-brand-50/30';
                                el.setAttribute('data-addon-group', '');
                                el.innerHTML = '<input type=hidden name=addon_groups[] value=' + selected + '><div class=text-sm font-medium text-gray-700>' + label + '</div><button type=button class=text-red-400 hover:text-red-600 text-xs ml-2>Retirer</button>';
                                el.querySelector('button').addEventListener('click', function() { el.remove(); });
                                $refs.list.appendChild(el);
                                opt.remove();
                                selected = '';
                            }"
                            class="px-3 py-2 bg-brand-600 text-white rounded-lg text-sm hover:bg-brand-700 transition">
                        Ajouter
                    </button>
                </div>
            @endif

            @if($addonGroups->isEmpty())
                <p class="text-sm text-gray-400 italic">Aucun groupe d'options défini. <a href="{{ route('admin.addon-groups.create') }}" class="text-brand-600 hover:underline">Créer un groupe</a></p>
            @endif
        </div>

        {{-- SEO --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 space-y-4">
            <h3 class="text-base font-semibold text-gray-800">SEO</h3>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Meta titre</label>
                <input type="text" name="meta_title" value="{{ old('meta_title', $product->meta_title ?? '') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Meta description</label>
                <textarea name="meta_description" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('meta_description', $product->meta_description ?? '') }}</textarea>
            </div>
        </div>
    </div>

    {{-- Right column --}}
    <div class="space-y-6">
        {{-- Publish --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 space-y-4">
            <h3 class="text-base font-semibold text-gray-800">Publication</h3>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Categorie</label>
                <select name="category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    <option value="">-- Aucune --</option>
                    @foreach($categories as $cat)
                        @if($cat->parent_id === null)
                            <optgroup label="{{ $cat->name }}">
                                @foreach($cat->children as $child)
                                    <option value="{{ $child->id }}" {{ old('category_id', $product->category_id ?? '') == $child->id ? 'selected' : '' }}>
                                        {{ $child->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </select>
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                Produit actif (visible en boutique)
            </label>

            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="hidden" name="is_featured" value="0">
                <input type="checkbox" name="is_featured" value="1"
                       {{ old('is_featured', $product->is_featured ?? false) ? 'checked' : '' }}
                       class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                Produit vedette (page d'accueil)
            </label>

            <div class="pt-2">
                <button type="submit" class="w-full py-2.5 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition">
                    {{ $isEdit ? 'Mettre a jour' : 'Creer le produit' }}
                </button>
            </div>
        </div>

        {{-- Image info --}}
        @if($isEdit && $product->featuredImage)
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">Image principale</h3>
                <img src="{{ $product->featuredImage->url }}" alt="{{ $product->name }}" class="w-full rounded-lg">
            </div>
        @endif
    </div>
</div>
