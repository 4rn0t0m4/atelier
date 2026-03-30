<x-layouts.app title="Mes coordonnées" :noindex="true">
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10"
     x-data="{ shippingDifferent: {{ auth()->user()->shipping_address_1 ? 'true' : 'false' }} }">

    <div class="flex items-center gap-3 mb-8">
        <a href="{{ route('account.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">Mon compte</a>
        <span class="text-gray-300">/</span>
        <h1 class="text-2xl font-semibold text-brand-900" style="font-family: Georgia, serif;">Mes coordonnées</h1>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded px-4 py-3 mb-6">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('account.address.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PATCH')

        {{-- Adresse de facturation --}}
        <section class="bg-white border border-brand-100 rounded-xl p-6">
            <h2 class="text-base font-semibold text-brand-900 mb-5">Adresse de facturation</h2>

            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Prénom *</label>
                        <input type="text" name="first_name"
                               value="{{ old('first_name', auth()->user()->first_name) }}"
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500 @error('first_name') border-red-400 @enderror"
                               required>
                        @error('first_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Nom *</label>
                        <input type="text" name="last_name"
                               value="{{ old('last_name', auth()->user()->last_name) }}"
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500 @error('last_name') border-red-400 @enderror"
                               required>
                        @error('last_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-gray-700 mb-1">Téléphone</label>
                    <input type="tel" name="phone"
                           value="{{ old('phone', auth()->user()->phone) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>

                <div>
                    <label class="block text-sm text-gray-700 mb-1">Adresse *</label>
                    <input type="text" name="address_1"
                           value="{{ old('address_1', auth()->user()->address_1) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500 @error('address_1') border-red-400 @enderror"
                           required>
                    @error('address_1')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm text-gray-700 mb-1">Complément d'adresse</label>
                    <input type="text" name="address_2"
                           value="{{ old('address_2', auth()->user()->address_2) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Code postal *</label>
                        <input type="text" name="postcode"
                               value="{{ old('postcode', auth()->user()->postcode) }}"
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500 @error('postcode') border-red-400 @enderror"
                               required>
                        @error('postcode')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Ville *</label>
                        <input type="text" name="city"
                               value="{{ old('city', auth()->user()->city) }}"
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500 @error('city') border-red-400 @enderror"
                               required>
                        @error('city')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-gray-700 mb-1">Pays *</label>
                    @php $userCountry = old('country', auth()->user()->country ?? 'FR'); @endphp
                    <select name="country"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500"
                            required>
                        @foreach(config('shipping.countries') as $code => $label)
                            <option value="{{ $code }}" {{ $userCountry === $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        {{-- Adresse de livraison --}}
        <section class="bg-white border border-brand-100 rounded-xl p-6">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-base font-semibold text-brand-900">Adresse de livraison</h2>
                <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                    <input type="checkbox" x-model="shippingDifferent"
                           class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                    Différente de la facturation
                </label>
            </div>

            <div x-show="!shippingDifferent" class="text-sm text-gray-500">
                L'adresse de livraison est identique à l'adresse de facturation.
            </div>

            <div x-show="shippingDifferent" x-transition class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Prénom</label>
                        <input type="text" name="shipping_first_name"
                               value="{{ old('shipping_first_name', auth()->user()->shipping_first_name) }}"
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Nom</label>
                        <input type="text" name="shipping_last_name"
                               value="{{ old('shipping_last_name', auth()->user()->shipping_last_name) }}"
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-gray-700 mb-1">Adresse</label>
                    <input type="text" name="shipping_address_1"
                           value="{{ old('shipping_address_1', auth()->user()->shipping_address_1) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>

                <div>
                    <label class="block text-sm text-gray-700 mb-1">Complément d'adresse</label>
                    <input type="text" name="shipping_address_2"
                           value="{{ old('shipping_address_2', auth()->user()->shipping_address_2) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Code postal</label>
                        <input type="text" name="shipping_postcode"
                               value="{{ old('shipping_postcode', auth()->user()->shipping_postcode) }}"
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-700 mb-1">Ville</label>
                        <input type="text" name="shipping_city"
                               value="{{ old('shipping_city', auth()->user()->shipping_city) }}"
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-gray-700 mb-1">Pays</label>
                    @php $shippingCountry = old('shipping_country', auth()->user()->shipping_country ?? 'FR'); @endphp
                    <select name="shipping_country"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                        @foreach(config('shipping.countries') as $code => $label)
                            <option value="{{ $code }}" {{ $shippingCountry === $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </section>

        <div class="flex justify-end">
            <button type="submit"
                    class="bg-brand-700 text-white py-2 px-5 rounded font-medium hover:bg-brand-800 transition text-sm">
                Enregistrer
            </button>
        </div>
    </form>
</div>
</x-layouts.app>
