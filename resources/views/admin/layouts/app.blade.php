<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Tableau de bord' }} | Admin Atelier d'Aubin</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>[x-cloak] { display: none !important; }</style>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('sidebar', {
                isExpanded: window.innerWidth >= 1024,
                isMobileOpen: false,
                isHovered: false,
                toggleExpanded() {
                    this.isExpanded = !this.isExpanded;
                    this.isMobileOpen = false;
                },
                toggleMobileOpen() {
                    this.isMobileOpen = !this.isMobileOpen;
                },
                setMobileOpen(val) {
                    this.isMobileOpen = val;
                },
                setHovered(val) {
                    if (window.innerWidth >= 1024 && !this.isExpanded) {
                        this.isHovered = val;
                    }
                }
            });
        });
    </script>
</head>

<body
    x-data
    x-init="$store.sidebar.isExpanded = window.innerWidth >= 1024;
        window.addEventListener('resize', () => {
            if (window.innerWidth < 1024) {
                $store.sidebar.setMobileOpen(false);
                $store.sidebar.isExpanded = false;
            } else {
                $store.sidebar.isMobileOpen = false;
                $store.sidebar.isExpanded = true;
            }
        });"
    class="bg-gray-50">

    <div class="min-h-screen lg:flex">
        @include('admin.layouts.backdrop')
        @include('admin.layouts.sidebar')

        <div class="flex-1 transition-all duration-300 ease-in-out"
            :class="{
                'lg:ml-[280px]': $store.sidebar.isExpanded || $store.sidebar.isHovered,
                'lg:ml-[80px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
                'ml-0': $store.sidebar.isMobileOpen
            }">
            @include('admin.layouts.header')

            <div class="p-4 mx-auto max-w-7xl md:p-6">
                @if (session('success'))
                    <x-admin.alert type="success" :message="session('success')" />
                @endif
                @if (session('error'))
                    <x-admin.alert type="error" :message="session('error')" />
                @endif

                @yield('content')
            </div>
        </div>
    </div>

</body>

@stack('scripts')

</html>
