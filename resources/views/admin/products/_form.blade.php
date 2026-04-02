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
                <textarea name="short_description" id="short_description" rows="2"
                          class="wysiwyg-light w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('short_description', $product->short_description ?? '') }}</textarea>
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Description</label>
                <textarea name="description" id="description" rows="6"
                          class="wysiwyg w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('description', $product->description ?? '') }}</textarea>
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
                                let labelText = opt.textContent.trim();
                                let el = document.createElement('div');
                                el.className = 'flex items-center justify-between p-3 rounded-lg border border-brand-100 bg-brand-50/30';
                                el.setAttribute('data-addon-group', '');
                                let input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'addon_groups[]';
                                input.value = selected;
                                let labelDiv = document.createElement('div');
                                labelDiv.className = 'text-sm font-medium text-gray-700';
                                labelDiv.textContent = labelText;
                                let removeBtn = document.createElement('button');
                                removeBtn.type = 'button';
                                removeBtn.className = 'text-red-400 hover:text-red-600 text-xs ml-2';
                                removeBtn.textContent = 'Retirer';
                                removeBtn.addEventListener('click', function() { el.remove(); });
                                el.appendChild(input);
                                el.appendChild(labelDiv);
                                el.appendChild(removeBtn);
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
                    @php
                        $renderOptions = function($items, $depth = 0) use (&$renderOptions, $product) {
                            foreach ($items as $cat) {
                                $prefix = str_repeat('— ', $depth);
                                $selected = old('category_id', $product->category_id ?? '') == $cat->id ? 'selected' : '';
                                echo "<option value=\"{$cat->id}\" {$selected}>{$prefix}{$cat->name}</option>";
                                if ($cat->children->isNotEmpty()) {
                                    $renderOptions($cat->children->sortBy('sort_order'), $depth + 1);
                                }
                            }
                        };
                        $renderOptions($categories->whereNull('parent_id'));
                    @endphp
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

        {{-- Images --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5"
             x-data="imageManager()"
             x-on:dragover.prevent="isDragging = true"
             x-on:dragleave.self="isDragging = false"
             x-on:drop.prevent="dropFiles($event)"
            <h3 class="text-sm font-semibold text-gray-800 mb-3">Photos</h3>

            <input type="hidden" name="featured_image_id" :value="featuredId">
            <input type="hidden" name="gallery_image_ids" :value="JSON.stringify(galleryIds)">

            {{-- Zone de drop / upload --}}
            <label class="flex flex-col items-center justify-center border-2 border-dashed border-gray-300 rounded-xl p-4 cursor-pointer hover:border-brand-400 hover:bg-brand-50/30 transition mb-4"
                   :class="{ 'border-brand-400 bg-brand-50/30': isDragging }">
                <svg class="w-8 h-8 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 16V4m0 0l-4 4m4-4l4 4M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/>
                </svg>
                <span class="text-xs text-gray-400">Glisser ou cliquer pour ajouter</span>
                <input type="file" multiple accept="image/*" class="hidden" @change="uploadFiles($event.target.files)">
            </label>

            {{-- Uploading indicator --}}
            <div x-show="uploading" class="text-center py-2">
                <svg class="animate-spin h-5 w-5 mx-auto text-brand-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <p class="text-xs text-gray-400 mt-1">Envoi en cours...</p>
            </div>

            {{-- Grid des images --}}
            <div class="grid grid-cols-2 gap-2" x-ref="imageGrid">
                <template x-for="(img, index) in images" :key="img.id">
                    <div class="relative group rounded-lg overflow-hidden border-2 cursor-grab"
                         :class="img.id == featuredId ? 'border-brand-500 ring-2 ring-brand-200' : 'border-gray-200'"
                         draggable="true"
                         @dragstart="dragStart(index, $event)"
                         @dragenter.prevent="dragEnter(index)"
                         @dragend="dragEnd()">
                        <img :src="img.url" :alt="img.alt || ''" class="w-full aspect-square object-cover">
                        {{-- Badge image principale --}}
                        <div x-show="img.id == featuredId"
                             class="absolute top-1 left-1 bg-brand-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">
                            Principale
                        </div>
                        {{-- Actions au hover --}}
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2">
                            <button type="button" @click="setFeatured(img.id)"
                                    class="p-1.5 bg-white rounded-full text-brand-600 hover:bg-brand-50" title="Image principale">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            </button>
                            <button type="button" @click="removeImage(index)"
                                    class="p-1.5 bg-white rounded-full text-red-500 hover:bg-red-50" title="Supprimer">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                        {{-- Drag indicator --}}
                        <div x-show="dragTargetIndex === index" class="absolute inset-0 border-2 border-brand-400 bg-brand-100/30 rounded-lg"></div>
                    </div>
                </template>
            </div>

            <p x-show="images.length === 0 && !uploading" class="text-xs text-gray-400 italic text-center py-2">Aucune photo</p>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const commonConfig = {
        language: 'fr_FR',
        branding: false,
        menubar: false,
        statusbar: false,
        promotion: false,
        skin: 'oxide',
        content_css: 'default',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; }',
    };

    // Description courte — toolbar simple
    tinymce.init({
        ...commonConfig,
        selector: '.wysiwyg-light',
        height: 120,
        plugins: 'link lists',
        toolbar: 'bold italic | link | bullist',
    });

    // Description complète — toolbar enrichie
    tinymce.init({
        ...commonConfig,
        selector: '.wysiwyg',
        height: 350,
        plugins: 'link lists table image code',
        toolbar: 'bold italic underline | headings | bullist numlist | table | link image | code',
        toolbar_groups: {
            headings: { icon: 'heading', tooltip: 'Titres', items: 'h2 h3 h4' }
        },
        image_advtab: true,
        table_toolbar: 'tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol',
    });
});

// Image manager Alpine component
function imageManager() {
    @php
        $existingImages = collect();
        if ($isEdit) {
            if ($product->featuredImage) {
                $existingImages->push($product->featuredImage);
            }
            if ($product->gallery_image_ids) {
                $gallery = \App\Models\Media::whereIn('id', $product->gallery_image_ids)
                    ->get()
                    ->sortBy(function ($m) use ($product) {
                        return array_search($m->id, $product->gallery_image_ids);
                    });
                foreach ($gallery as $img) {
                    if (!$existingImages->contains('id', $img->id)) {
                        $existingImages->push($img);
                    }
                }
            }
        }
    @endphp

    return {
        images: {!! $existingImages->map(fn($m) => ['id' => $m->id, 'url' => $m->url, 'alt' => $m->alt])->values()->toJson() !!},
        featuredId: {{ $product->featured_image_id ?? 'null' }},
        uploading: false,
        isDragging: false,
        dragIndex: null,
        dragTargetIndex: null,

        get galleryIds() {
            return this.images.filter(img => img.id != this.featuredId).map(img => img.id);
        },

        async uploadFiles(files) {
            this.uploading = true;
            for (const file of files) {
                const formData = new FormData();
                formData.append('image', file);

                try {
                    const res = await fetch('{{ route("admin.media.upload") }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: formData,
                    });
                    const data = await res.json();
                    if (data.id) {
                        this.images.push({ id: data.id, url: data.url, alt: '' });
                        if (!this.featuredId) this.featuredId = data.id;
                    }
                } catch (e) {
                    console.error('Upload failed:', e);
                }
            }
            this.uploading = false;
        },

        dropFiles(e) {
            this.isDragging = false;
            if (e.dataTransfer.files.length) {
                this.uploadFiles(e.dataTransfer.files);
            }
        },

        setFeatured(id) {
            this.featuredId = id;
        },

        removeImage(index) {
            const img = this.images[index];
            this.images.splice(index, 1);
            if (img.id == this.featuredId) {
                this.featuredId = this.images.length ? this.images[0].id : null;
            }
        },

        // Drag & drop reorder
        dragStart(index, e) {
            this.dragIndex = index;
            e.dataTransfer.effectAllowed = 'move';
        },
        dragEnter(index) {
            if (this.dragIndex === null || this.dragIndex === index) return;
            this.dragTargetIndex = index;
            const item = this.images.splice(this.dragIndex, 1)[0];
            this.images.splice(index, 0, item);
            this.dragIndex = index;
        },
        dragEnd() {
            this.dragIndex = null;
            this.dragTargetIndex = null;
        },
    };
}
</script>
@endpush
