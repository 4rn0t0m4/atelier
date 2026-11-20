{{-- App installable sur telephone + notifications push (voir public/admin-sw.js) --}}
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
</style>

<script>
    window.adminPushConfig = {{ Illuminate\Support\Js::from([
        'vapidKey' => config('services.webpush.public_key'),
        'subscribeUrl' => route('admin.push.subscribe'),
        'unsubscribeUrl' => route('admin.push.unsubscribe'),
        'testUrl' => route('admin.push.test'),
    ]) }};

    // Chrome/Android : on garde l'invite d'installation pour la proposer depuis la cloche
    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        window.adminInstallPrompt = event;
        window.dispatchEvent(new CustomEvent('admin-installable'));
    });

    document.addEventListener('alpine:init', () => {
        const config = window.adminPushConfig;

        const toUint8Array = (base64Url) => {
            const base64 = (base64Url + '='.repeat((4 - base64Url.length % 4) % 4)).replace(/-/g, '+').replace(/_/g, '/');

            return Uint8Array.from(atob(base64), (char) => char.charCodeAt(0));
        };

        const send = (method, url, body = {}) => fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(body),
        });

        // Le serveur est resynchronise au plus une fois par jour et par appareil
        const sync = async (subscription, force = false) => {
            const stamp = subscription.endpoint + '|' + new Date().toISOString().slice(0, 10);
            if (!force && localStorage.getItem('adminPushSync') === stamp) {
                return true;
            }

            const encodings = PushManager.supportedContentEncodings || [];
            const response = await send('POST', config.subscribeUrl, {
                ...subscription.toJSON(),
                contentEncoding: encodings.includes('aes128gcm') ? 'aes128gcm' : 'aesgcm',
            });
            if (response.ok) {
                localStorage.setItem('adminPushSync', stamp);
            }

            return response.ok;
        };

        Alpine.data('adminPush', () => ({
            open: false,
            // loading | unsupported | ios-install | unconfigured | denied | off | on
            state: 'loading',
            busy: false,
            message: '',
            messageType: 'success',
            canInstall: Boolean(window.adminInstallPrompt),

            async init() {
                window.addEventListener('admin-installable', () => this.canInstall = true);

                const standalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
                const ios = /iPad|iPhone|iPod/.test(navigator.userAgent)
                    || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

                if (!('serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window)) {
                    // Sur iPhone, le push n'existe que dans l'app ajoutee a l'ecran d'accueil
                    this.state = ios && !standalone ? 'ios-install' : 'unsupported';

                    return;
                }

                try {
                    const registration = await navigator.serviceWorker.register('/admin-sw.js', { scope: '/admin' });

                    if (!config.vapidKey) {
                        this.state = 'unconfigured';
                    } else if (Notification.permission === 'denied') {
                        this.state = 'denied';
                    } else {
                        const subscription = await registration.pushManager.getSubscription();
                        this.state = subscription ? 'on' : 'off';
                        if (subscription) {
                            sync(subscription).catch(() => {});
                        }
                    }
                } catch (e) {
                    this.state = 'unsupported';
                }
            },

            flash(message, type = 'success') {
                this.message = message;
                this.messageType = type;
            },

            async enable() {
                this.busy = true;
                this.message = '';

                try {
                    const permission = await Notification.requestPermission();
                    if (permission !== 'granted') {
                        this.state = permission === 'denied' ? 'denied' : 'off';

                        return;
                    }

                    const registration = await navigator.serviceWorker.ready;
                    // Un ancien abonnement lie a d'autres cles VAPID empecherait le nouveau
                    await (await registration.pushManager.getSubscription())?.unsubscribe();

                    const subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: toUint8Array(config.vapidKey),
                    });

                    if (!(await sync(subscription, true))) {
                        throw new Error('sync');
                    }

                    this.state = 'on';
                    this.flash('Notifications activées sur cet appareil.');
                } catch (e) {
                    this.flash("Impossible d'activer les notifications sur cet appareil.", 'error');
                } finally {
                    this.busy = false;
                }
            },

            async disable() {
                this.busy = true;
                this.message = '';

                try {
                    const registration = await navigator.serviceWorker.ready;
                    const subscription = await registration.pushManager.getSubscription();

                    if (subscription) {
                        await send('DELETE', config.unsubscribeUrl, { endpoint: subscription.endpoint });
                        await subscription.unsubscribe();
                    }

                    localStorage.removeItem('adminPushSync');
                    this.state = 'off';
                    this.flash('Notifications désactivées sur cet appareil.');
                } catch (e) {
                    this.flash('Une erreur est survenue.', 'error');
                } finally {
                    this.busy = false;
                }
            },

            async test() {
                this.busy = true;
                this.message = '';

                try {
                    const data = await (await send('POST', config.testUrl)).json();
                    this.flash(data.success || data.error || 'Une erreur est survenue.', data.success ? 'success' : 'error');
                } catch (e) {
                    this.flash('Une erreur est survenue.', 'error');
                } finally {
                    this.busy = false;
                }
            },

            async install() {
                const prompt = window.adminInstallPrompt;
                if (!prompt) {
                    return;
                }

                prompt.prompt();
                await prompt.userChoice;
                window.adminInstallPrompt = null;
                this.canInstall = false;
            },
        }));
    });
</script>
