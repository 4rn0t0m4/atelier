@php $isEdit = isset($tag); @endphp

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <div class="xl:col-span-2 space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 space-y-4">
            <h3 class="text-base font-semibold text-gray-800">Informations</h3>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Nom *</label>
                <input type="text" name="name" value="{{ old('name', $tag->name ?? '') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500 @error('name') border-red-400 @enderror">
                @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">Slug (URL)</label>
                <input type="text" name="slug" value="{{ old('slug', $tag->slug ?? '') }}"
                       placeholder="auto-genere si vide"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500 font-mono text-xs">
                @error('slug')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 space-y-4">
            <button type="submit" class="w-full py-2.5 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition">
                {{ $isEdit ? 'Mettre a jour' : 'Creer le tag' }}
            </button>
            <a href="{{ route('admin.tags.index') }}" class="block text-center text-sm text-gray-500 hover:text-gray-700">Annuler</a>
        </div>

        @if($isEdit)
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <p class="text-xs text-gray-400">{{ $tag->products()->count() }} produit(s) associé(s)</p>
            </div>
        @endif
    </div>
</div>
