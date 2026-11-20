{{-- Barre d'onglets mobile : les ecrans utiles au quotidien, le reste dans le menu --}}
@php
    $tabs = [
        ['label' => 'Commandes', 'href' => route('admin.orders.index'), 'active' => request()->routeIs('admin.orders.*'), 'badge' => $processingCount,
            'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
        ['label' => 'Accueil', 'href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard'), 'badge' => 0,
            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['label' => 'Produits', 'href' => route('admin.products.index'), 'active' => request()->routeIs('admin.products.*'), 'badge' => 0,
            'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
    ];
@endphp

<nav class="lg:hidden fixed bottom-0 inset-x-0 z-[99990] bg-white border-t border-gray-200"
     style="padding-bottom: env(safe-area-inset-bottom)"
     x-data
     x-init="'setAppBadge' in navigator && ({{ $processingCount }} > 0
         ? navigator.setAppBadge({{ $processingCount }})
         : navigator.clearAppBadge()).catch(() => {})">
    <div class="grid grid-cols-4">
        @foreach ($tabs as $tab)
            <a href="{{ $tab['href'] }}"
               @class([
                   'relative flex flex-col items-center gap-0.5 py-2 text-[11px] font-medium',
                   'text-brand-700' => $tab['active'],
                   'text-gray-500' => ! $tab['active'],
               ])>
                <span class="relative">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $tab['icon'] }}"/>
                    </svg>
                    @if ($tab['badge'] > 0)
                        <span class="absolute -top-1.5 -right-2.5 min-w-[18px] h-[18px] px-1 rounded-full bg-brand-600 text-white text-[10px] font-semibold leading-[18px] text-center">
                            {{ $tab['badge'] > 99 ? '99+' : $tab['badge'] }}
                        </span>
                    @endif
                </span>
                {{ $tab['label'] }}
            </a>
        @endforeach

        <button type="button" @click="$store.sidebar.toggleMobileOpen()"
            class="flex flex-col items-center gap-0.5 py-2 text-[11px] font-medium text-gray-500">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            Menu
        </button>
    </div>
</nav>
