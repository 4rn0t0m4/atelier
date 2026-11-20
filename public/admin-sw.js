/*
 * Service worker de l'admin : notifications push uniquement.
 * Volontairement sans gestionnaire `fetch` : aucune page ni aucun asset n'est
 * mis en cache, la boutique et l'admin restent toujours servis par le serveur.
 */

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('push', (event) => {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = { body: event.data.text() };
    }

    const tasks = [
        self.registration.showNotification(data.title || "Atelier d'Aubin", {
            body: data.body || '',
            icon: '/images/pwa/icon-192.png',
            badge: '/images/pwa/badge-96.png',
            // Une meme commande notifiee deux fois ne s'affiche qu'une fois
            tag: data.tag,
            // Android n'offre qu'une categorie, donc un seul son, par app web : les
            // notifications silencieuses le reservent aux ventes. Une notification
            // silencieuse ne peut pas etre « renotifiee » (le navigateur la refuse).
            silent: Boolean(data.silent),
            renotify: Boolean(data.tag) && !data.silent,
            data: { url: data.url || '/admin/commandes' },
        }),
    ];

    if (typeof data.badgeCount === 'number' && 'setAppBadge' in self.navigator) {
        tasks.push(self.navigator.setAppBadge(data.badgeCount).catch(() => {}));
    }

    event.waitUntil(Promise.all(tasks));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = new URL(event.notification.data?.url || '/admin/commandes', self.location.origin).href;

    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        const admin = windows.find((client) => new URL(client.url).pathname.startsWith('/admin'));

        if (admin) {
            try {
                await admin.focus();
                await admin.navigate(url);

                return;
            } catch (e) {
                // Fenetre non pilotable (ex. iOS) : on en ouvre une nouvelle
            }
        }

        await self.clients.openWindow(url);
    })());
});
