@php $p = $page ?? null; @endphp

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    {{-- Colonne principale --}}
    <div class="xl:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-700 uppercase mb-4">Contenu</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Titre *</label>
                    <input type="text" name="title" id="title" value="{{ old('title', $p?->title) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                    <input type="text" name="slug" id="slug" value="{{ old('slug', $p?->slug) }}" placeholder="Auto-généré"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    @error('slug') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-4">
                <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Contenu</label>
                <textarea name="content" id="content" rows="20"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-brand-500 focus:border-brand-500">{{ old('content', $p?->content) }}</textarea>
                @error('content') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                <p class="text-xs text-gray-400 mt-1">HTML autorisé</p>
            </div>
        </div>

        {{-- SEO --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-700 uppercase mb-4">SEO</h3>

            <div class="space-y-4">
                <div>
                    <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-1">Meta title <span class="text-gray-400">(max 70)</span></label>
                    <input type="text" name="meta_title" id="meta_title" value="{{ old('meta_title', $p?->meta_title) }}" maxlength="70"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-1">Meta description <span class="text-gray-400">(max 160)</span></label>
                    <textarea name="meta_description" id="meta_description" rows="2" maxlength="160"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('meta_description', $p?->meta_description) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-700 uppercase mb-4">Publication</h3>

            <div class="space-y-4">
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $p?->is_published ?? true))
                           class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    Publiée
                </label>

                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">Ordre d'affichage</label>
                    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $p?->sort_order ?? 0) }}" min="0"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <button type="submit" class="w-full py-2.5 px-4 text-sm font-medium text-white bg-brand-700 rounded-lg hover:bg-brand-800 transition">
                {{ $p ? 'Mettre à jour' : 'Créer la page' }}
            </button>
            <a href="{{ route('admin.pages.index') }}" class="block text-center mt-3 text-sm text-gray-500 hover:text-gray-700">Annuler</a>
        </div>
    </div>
</div>
