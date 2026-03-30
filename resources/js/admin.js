import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Sidebar store
Alpine.store('sidebar', {
    isOpen: false,
    isHovered: false,
    isMobileOpen: false,

    toggle() {
        this.isOpen = !this.isOpen;
    },

    openMobile() {
        this.isMobileOpen = true;
    },

    closeMobile() {
        this.isMobileOpen = false;
    },
});

Alpine.start();
