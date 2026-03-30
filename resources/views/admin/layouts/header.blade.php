<header class="sticky top-0 flex w-full bg-white border-b border-gray-200 z-[99998]">
    <div class="flex items-center justify-between w-full px-3 py-3 sm:px-4 lg:px-6 lg:py-4">

        {{-- Desktop sidebar toggle --}}
        <button
            class="hidden lg:flex items-center justify-center w-10 h-10 text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50"
            @click="$store.sidebar.toggleExpanded()" aria-label="Toggle Sidebar">
            <svg width="16" height="12" viewBox="0 0 16 12" fill="none">
                <path fill-rule="evenodd" clip-rule="evenodd"
                    d="M0.583 1C0.583 0.586 0.919 0.25 1.333 0.25H14.667C15.081 0.25 15.417 0.586 15.417 1C15.417 1.414 15.081 1.75 14.667 1.75L1.333 1.75C0.919 1.75 0.583 1.414 0.583 1ZM0.583 11C0.583 10.586 0.919 10.25 1.333 10.25L14.667 10.25C15.081 10.25 15.417 10.586 15.417 11C15.417 11.414 15.081 11.75 14.667 11.75L1.333 11.75C0.919 11.75 0.583 11.414 0.583 11ZM1.333 5.25C0.919 5.25 0.583 5.586 0.583 6C0.583 6.414 0.919 6.75 1.333 6.75L8 6.75C8.414 6.75 8.75 6.414 8.75 6C8.75 5.586 8.414 5.25 8 5.25L1.333 5.25Z"
                    fill="currentColor"></path>
            </svg>
        </button>

        {{-- Mobile menu toggle --}}
        <button
            class="flex lg:hidden items-center justify-center w-10 h-10 text-gray-500 rounded-lg"
            @click="$store.sidebar.toggleMobileOpen()" aria-label="Menu">
            <svg x-show="!$store.sidebar.isMobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <svg x-show="$store.sidebar.isMobileOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        {{-- Mobile logo --}}
        <a href="{{ route('admin.dashboard') }}" class="lg:hidden font-bold text-lg text-brand-900">
            Admin Atelier
        </a>

        {{-- Right side --}}
        <div class="flex items-center gap-2">
            {{-- View site --}}
            <a href="/" target="_blank"
               class="hidden sm:flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                Voir le site
            </a>

            {{-- User dropdown --}}
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open"
                    class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                    <span class="hidden sm:inline">{{ auth()->user()->first_name ?? auth()->user()->email }}</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false" x-transition x-cloak
                    class="absolute right-0 mt-2 w-48 rounded-lg border border-gray-200 bg-white py-2 shadow-lg">
                    <a href="/" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        Voir le site
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Deconnexion
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
