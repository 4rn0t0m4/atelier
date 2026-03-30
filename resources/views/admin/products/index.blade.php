@extends('admin.layouts.app')

@section('content')
    <x-admin.page-breadcrumb title="Produits" :breadcrumbs="['Produits' => null]" />

    {{-- Filters --}}
    <div class="rounded-2xl border border-gray-200 bg-white mb-6">
        <form action="{{ route('admin.products.index') }}" method="GET" class="p-4 md:p-5">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs text-gray-500 mb-1">Recherche</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Nom du produit..."
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div class="w-44">
                    <label class="block text-xs text-gray-500 mb-1">Categorie</label>
                    <select name="category" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                        <option value="">Toutes</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-36">
                    <label class="block text-xs text-gray-500 mb-1">Statut</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-brand-500 focus:border-brand-500">
                        <option value="">Tous</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-brand-600 text-white rounded-lg text-sm font-medium hover:bg-brand-700 transition">
                    Filtrer
                </button>
                @if(request()->hasAny(['search', 'category', 'status']))
                    <a href="{{ route('admin.products.index') }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700">Reinitialiser</a>
                @endif
                <a href="{{ route('admin.products.create') }}" class="ml-auto px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition">
                    + Nouveau produit
                </a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="rounded-2xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="px-5 py-3 text-left text-sm font-medium text-gray-500">Produit</th>
                        <th class="px-5 py-3 text-left text-sm font-medium text-gray-500">Categorie</th>
                        <th class="px-5 py-3 text-right text-sm font-medium text-gray-500">Prix</th>
                        <th class="px-5 py-3 text-right text-sm font-medium text-gray-500">Stock</th>
                        <th class="px-5 py-3 text-center text-sm font-medium text-gray-500">Actif</th>
                        <th class="px-5 py-3 text-right text-sm font-medium text-gray-500"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    @if($product->featuredImage)
                                        <img src="{{ $product->featuredImage->url }}" alt="" class="w-10 h-10 rounded object-cover">
                                    @else
                                        <div class="w-10 h-10 rounded bg-gray-100 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <div>
                                        <a href="{{ route('admin.products.edit', $product) }}" class="text-sm font-medium text-gray-700 hover:text-brand-600">{{ $product->name }}</a>
                                        @if($product->sku)
                                            <div class="text-xs text-gray-400">{{ $product->sku }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-500">
                                {{ $product->category?->name ?? '-' }}
                            </td>
                            <td class="px-5 py-4 text-sm text-right">
                                @if($product->sale_price)
                                    <span class="line-through text-gray-400">{{ number_format($product->price, 2, ',', ' ') }}</span>
                                    <span class="text-red-600 font-medium">{{ number_format($product->sale_price, 2, ',', ' ') }} &euro;</span>
                                @else
                                    <span class="text-gray-700">{{ number_format($product->price, 2, ',', ' ') }} &euro;</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-right">
                                @if($product->manage_stock)
                                    <span class="{{ $product->stock_quantity > 0 ? 'text-gray-700' : 'text-red-600 font-medium' }}">
                                        {{ $product->stock_quantity }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center">
                                <button onclick="toggleActive({{ $product->id }}, this)"
                                        class="w-8 h-5 rounded-full relative transition-colors {{ $product->is_active ? 'bg-green-500' : 'bg-gray-300' }}">
                                    <span class="block w-3.5 h-3.5 rounded-full bg-white shadow absolute top-0.5 transition-transform {{ $product->is_active ? 'translate-x-3.5' : 'translate-x-0.5' }}"></span>
                                </button>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.products.edit', $product) }}" class="text-gray-400 hover:text-brand-600" title="Modifier">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                                          onsubmit="return confirm('Supprimer ce produit ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-gray-400 hover:text-red-600" title="Supprimer">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-500">
                                Aucun produit trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
            <div class="px-5 py-4 border-t border-gray-200">
                {{ $products->links() }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    function toggleActive(id, btn) {
        fetch(`/admin/produits/${id}/toggle-active`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(data => {
            const dot = btn.querySelector('span');
            if (data.is_active) {
                btn.classList.remove('bg-gray-300');
                btn.classList.add('bg-green-500');
                dot.classList.remove('translate-x-0.5');
                dot.classList.add('translate-x-3.5');
            } else {
                btn.classList.remove('bg-green-500');
                btn.classList.add('bg-gray-300');
                dot.classList.remove('translate-x-3.5');
                dot.classList.add('translate-x-0.5');
            }
        });
    }
</script>
@endpush
