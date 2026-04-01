@props(['product'])

<article class="group flex flex-col bg-white border border-brand-200 rounded-xl overflow-hidden hover:shadow-md transition-shadow {{ !$product->is_active ? 'opacity-60' : '' }}">
    {{-- Admin badge --}}
    @if(!$product->is_active && auth()->user()?->is_admin)
        <div class="bg-amber-500 text-white text-xs font-semibold text-center py-1" role="status">Masque — visible uniquement par les admins</div>
    @endif

    {{-- Image --}}
    <a href="{{ $product->url() }}" data-turbo-frame="_top"
       class="block aspect-square overflow-hidden bg-brand-50">
        @if($product->featured_image_id && $product->featuredImage)
            <img src="{{ $product->featuredImage->url }}"
                 alt="{{ $product->name }}"
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                 loading="lazy">
        @else
            <div class="w-full h-full flex items-center justify-center text-brand-200">
                <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif
    </a>

    {{-- Infos --}}
    <div class="p-3 flex flex-col flex-1">
        @if($product->category)
            <p class="text-xs mb-1 text-brand-500">{{ $product->category->name }}</p>
        @endif
        <a href="{{ $product->url() }}" data-turbo-frame="_top"
           class="text-sm font-medium leading-snug flex-1 hover:opacity-70 transition-opacity text-brand-700"
           style="font-family: Georgia, serif;">
            {{ $product->name }}
        </a>

        {{-- Etoiles --}}
        @if(($product->reviews_count ?? 0) > 0)
            <div class="flex items-center gap-1 mt-1.5">
                @php $avg = round($product->reviews_avg ?? 0, 1); @endphp
                <div class="flex items-center gap-px">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= floor($avg))
                            <svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @elseif($i - $avg < 1 && $i - $avg > 0)
                            <svg class="w-3.5 h-3.5 text-amber-400" viewBox="0 0 20 20">
                                <defs><linearGradient id="half-star-{{ $product->id }}"><stop offset="50%" stop-color="currentColor"/><stop offset="50%" stop-color="#d1d5db"/></linearGradient></defs>
                                <path fill="url(#half-star-{{ $product->id }})" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @else
                            <svg class="w-3.5 h-3.5 text-gray-300" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endif
                    @endfor
                </div>
                <span class="text-xs text-brand-400">({{ $product->reviews_count }})</span>
            </div>
        @endif

        {{-- Prix + ajout panier --}}
        <div class="flex items-center justify-between mt-3">
            <div>
                @if($product->price == 0 && $product->category && in_array($product->category->slug, ['lettres-en-bois', 'lettre-en-bois-3d']))
                    @php
                        $minPrice = $product->getAllAddonGroups()
                            ->flatMap->addons
                            ->where('label', 'Taille des lettres')
                            ->flatMap(fn($a) => collect($a->options)->pluck('price'))
                            ->filter()->min() ?: 0;
                    @endphp
                    <span class="text-sm font-semibold text-brand-700">Dès {{ number_format($minPrice, 2, ',', ' ') }} € / lettre</span>
                @elseif($product->sale_price)
                    <span class="text-xs line-through mr-1 text-brand-400">{{ number_format($product->price, 2, ',', ' ') }} €</span>
                    <span class="text-sm font-semibold text-brand-700">{{ number_format($product->sale_price, 2, ',', ' ') }} €</span>
                @else
                    <span class="text-sm font-semibold text-brand-700">{{ number_format($product->price, 2, ',', ' ') }} €</span>
                @endif
            </div>
            @php $inStock = $product->isInStock(); @endphp
            @if(!$inStock)
                <span class="text-xs px-2.5 py-1 rounded-lg text-white bg-gray-400 opacity-50">Epuise</span>
            @else
                <a href="{{ $product->url() }}"
                   class="text-xs px-2.5 py-1 rounded-lg text-white bg-brand-600 hover:opacity-90 transition">
                    Choisir
                </a>
            @endif
        </div>
    </div>
</article>
