@php $rule = $discount ?? null; @endphp

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    {{-- Colonne principale --}}
    <div class="xl:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-700 uppercase mb-4">Informations</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $rule?->name) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="coupon_code" class="block text-sm font-medium text-gray-700 mb-1">Code coupon</label>
                    <input type="text" name="coupon_code" id="coupon_code" value="{{ old('coupon_code', $rule?->coupon_code) }}" placeholder="Laisser vide = automatique" maxlength="50"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm uppercase focus:ring-brand-500 focus:border-brand-500">
                    @error('coupon_code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select name="type" id="type"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                        <option value="coupon" @selected(old('type', $rule?->type) === 'coupon')>Coupon</option>
                        <option value="automatic" @selected(old('type', $rule?->type) === 'automatic')>Automatique</option>
                    </select>
                </div>

                <div>
                    <label for="discount_type" class="block text-sm font-medium text-gray-700 mb-1">Type de réduction</label>
                    <select name="discount_type" id="discount_type"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                        <option value="percentage" @selected(old('discount_type', $rule?->discount_type) === 'percentage')>Pourcentage (%)</option>
                        <option value="fixed_cart" @selected(old('discount_type', $rule?->discount_type) === 'fixed_cart')>Montant fixe (€)</option>
                    </select>
                </div>

                <div>
                    <label for="discount_amount" class="block text-sm font-medium text-gray-700 mb-1">Montant *</label>
                    <input type="number" name="discount_amount" id="discount_amount" value="{{ old('discount_amount', $rule?->discount_amount) }}" required min="0" step="0.01"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    @error('discount_amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Conditions --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-700 uppercase mb-4">Conditions</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="min_cart_value" class="block text-sm font-medium text-gray-700 mb-1">Montant min. panier (€)</label>
                    <input type="number" name="min_cart_value" id="min_cart_value" value="{{ old('min_cart_value', $rule?->min_cart_value) }}" min="0" step="0.01"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>

                <div>
                    <label for="max_cart_value" class="block text-sm font-medium text-gray-700 mb-1">Montant max. panier (€)</label>
                    <input type="number" name="max_cart_value" id="max_cart_value" value="{{ old('max_cart_value', $rule?->max_cart_value) }}" min="0" step="0.01"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>

                <div>
                    <label for="min_quantity" class="block text-sm font-medium text-gray-700 mb-1">Quantité min.</label>
                    <input type="number" name="min_quantity" id="min_quantity" value="{{ old('min_quantity', $rule?->min_quantity) }}" min="0"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>

                <div>
                    <label for="usage_limit" class="block text-sm font-medium text-gray-700 mb-1">Limite d'utilisation</label>
                    <input type="number" name="usage_limit" id="usage_limit" value="{{ old('usage_limit', $rule?->usage_limit) }}" min="0" placeholder="Illimité"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Catégories ciblées</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3">
                    @foreach($categories as $cat)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="target_categories[]" value="{{ $cat->id }}"
                                   @checked(in_array($cat->id, old('target_categories', $rule?->target_categories ?? [])))
                                   class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                            {{ $cat->name }}
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-gray-400 mt-1">Laisser vide = toutes les catégories</p>
            </div>
        </div>

        {{-- Période --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-700 uppercase mb-4">Période de validité</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="starts_at" class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                    <input type="date" name="starts_at" id="starts_at" value="{{ old('starts_at', $rule?->starts_at?->format('Y-m-d')) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>

                <div>
                    <label for="ends_at" class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                    <input type="date" name="ends_at" id="ends_at" value="{{ old('ends_at', $rule?->ends_at?->format('Y-m-d')) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    @error('ends_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-700 uppercase mb-4">Options</h3>

            <div class="space-y-3">
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $rule?->is_active ?? true))
                           class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    Actif
                </label>

                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="free_shipping" value="0">
                    <input type="checkbox" name="free_shipping" value="1" @checked(old('free_shipping', $rule?->free_shipping ?? false))
                           class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    Livraison offerte
                </label>

                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="stackable" value="0">
                    <input type="checkbox" name="stackable" value="1" @checked(old('stackable', $rule?->stackable ?? false))
                           class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    Cumulable
                </label>

                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="exclude_sale_items" value="0">
                    <input type="checkbox" name="exclude_sale_items" value="1" @checked(old('exclude_sale_items', $rule?->exclude_sale_items ?? false))
                           class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    Exclure articles en promo
                </label>
            </div>

            @if($rule)
            <div class="mt-4 pt-4 border-t border-gray-100 text-xs text-gray-400">
                Utilisé {{ $rule->usage_count }} fois
            </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <button type="submit" class="w-full py-2.5 px-4 text-sm font-medium text-white bg-brand-700 rounded-lg hover:bg-brand-800 transition">
                {{ $rule ? 'Mettre à jour' : 'Créer la réduction' }}
            </button>
            <a href="{{ route('admin.discounts.index') }}" class="block text-center mt-3 text-sm text-gray-500 hover:text-gray-700">Annuler</a>
        </div>
    </div>
</div>
