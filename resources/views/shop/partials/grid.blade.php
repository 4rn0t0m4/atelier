<turbo-frame id="products-grid">
@if($products->isEmpty())
    <p class="text-gray-500 text-sm py-12 text-center">Aucun produit trouve.</p>
@else
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($products as $product)
            <x-product-card :product="$product"/>
        @endforeach
    </div>

    @if($products->hasPages())
        <div class="mt-10">
            {{ $products->links() }}
        </div>
    @endif
@endif
</turbo-frame>
