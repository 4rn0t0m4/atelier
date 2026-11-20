{{-- App installable sur telephone (voir public/admin.webmanifest) --}}
<link rel="manifest" href="/admin.webmanifest">
<meta name="theme-color" content="#ffffff">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Atelier">
<link rel="apple-touch-icon" href="/images/pwa/apple-touch-icon.png">

<style>
    /* iOS zoome sur tout champ dont le texte fait moins de 16px */
    @media (max-width: 767px) {
        input, select, textarea { font-size: 16px !important; }
    }

    /* Dans l'app installee, aucun lien ne mene a la boutique */
    @media (display-mode: standalone) {
        .browser-only { display: none !important; }
    }
</style>

<script>
    // Marque l'app installee de l'admin : si elle se retrouve sur une page de la
    // boutique, celle-ci la renvoie vers l'admin (voir components/layouts/app.blade.php)
    try {
        if (window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true) {
            localStorage.setItem('adminApp', '1');
        }
    } catch (e) {}
</script>
