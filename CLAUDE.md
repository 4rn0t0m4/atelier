# Atelier d'Aubin — Laravel

Migration du site e-commerce atelier-aubin.fr (WordPress/WooCommerce) vers Laravel.

## Stack
- Laravel 10, PHP 8.1+
- Blade + Tailwind CSS v4 + Alpine.js + Hotwired Turbo
- MySQL (atelier_laravel) + connexion wp_legacy (atelier_db, prefix mod745_)
- Stripe (PaymentIntent) + PayPal
- Boxtal (points relais) + Colissimo
- Brevo (emails transactionnels)
- barryvdh/laravel-dompdf (factures PDF)

## Conventions
- Tout le texte UI est en **français**
- Prix stockés en `decimal(10,2)`, affichés avec `number_format($price, 2, ',', ' ') . ' €'`
- Numéros de commande : `CMD-{uniqid}`
- Authentification custom (pas Breeze/Fortify), admin via `is_admin` boolean sur User
- Routes admin préfixées `/admin`, middleware `auth` + `admin`
- Frontend : Turbo actif, formulaires POST utiliser `data-turbo="false"` si besoin
- Cart : session-based, unicité par `product_id + md5(serialized_addons)`
- Mot de passe User : cast `'password' => 'hashed'` (ne jamais bcrypt() manuellement)

## Système d'addons produit
Le coeur du projet. Trois types de prix :
- `flat_fee` : ajouté 1 fois quel que soit la quantité
- `quantity_based` : multiplié par la quantité
- `percentage_based` : % du prix de base

## Admin installable (PWA) et notifications push
- Manifest `public/admin.webmanifest`, service worker `public/admin-sw.js` (scope `/admin`, push uniquement, aucun cache)
- Clés VAPID : `php artisan webpush:vapid` puis `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` dans `.env`
- `AdminPushService::notifyNewOrder()` est appelé aux 3 points de confirmation de paiement (webhook Stripe, page success, PayPal) et ne lève jamais d'exception
- Abonnements par appareil dans `push_subscriptions` ; activation via la cloche du header admin
- Android n'offre qu'une catégorie de notification (donc un seul son) par app web : les alertes de connexion partent avec `silent: true` pour que le son réglé sur le téléphone ne sonne que pour les ventes
- `public/build` est versionné : relancer `npm run build` après tout changement de classes Tailwind
- L'app ne sert qu'à l'admin (manifest `scope: /admin`) : connexion, code de vérification et déconnexion ont leurs routes sous `/admin/connexion` et `/admin/deconnexion` (mêmes méthodes d'`AuthController`, qui détecte le contexte par l'URL)
- Filet de sécurité : l'app installée pose `localStorage.adminApp` ; si elle atterrit sur une page de la boutique, `components/layouts/app.blade.php` la renvoie vers `/admin/commandes`

## Sécurité des comptes
- Connexion : blocage 15 min après 5 échecs pour un couple email + IP (en plus du `throttle` de route)
- Toute connexion à un compte admin déclenche une alerte push + email (`AuthController::alertAdminLogin`)
- `AuthenticateSession` est actif sur le groupe `web` : changer le mot de passe déconnecte les autres appareils
- `SecurityHeaders` (middleware global) : en-têtes de sécurité partout, `no-store` + `noindex` sur `/admin`
- Double authentification TOTP (Google Authenticator), activable par compte admin dans `/admin/securite` (`TwoFactorService`)
  - Connexion : mot de passe, puis code sur `/connexion/verification` ; la case « 90 jours » pose le cookie `trusted_device` et limite « se souvenir de moi » à 90 jours
  - Le cookie est signé avec le mot de passe et le secret : changer l'un des deux oublie tous les appareils mémorisés
  - Téléphone et codes de secours perdus : `php artisan admin:2fa-disable {email}`

## Base de données legacy (migration)
- Connexion `wp_legacy` dans config/database.php
- Prefix tables : `mod745_`
- Addons WC stockés en arrays PHP sérialisés dans `postmeta` (clé `_product_addons`)

## Commandes utiles
```bash
php artisan serve              # Serveur dev
npm run dev                    # Vite dev
php artisan migrate            # Migrations
php artisan migrate:wp-XXX     # Commandes de migration WP
```

## Réexpédition en relais (colis retourné)
- Table `order_reshipments` : une demande par lien, statut `pending` / `paid` / `cancelled`, montant fixé par l'admin
- Admin : bloc « Réexpédition en relais » sur la fiche commande (`admin/orders/_reshipment.blade.php`) → crée le lien, envoie l'email `ReshipmentRequest`, peut renvoyer ou annuler ; créer une nouvelle demande annule la précédente
- Client : `/reexpedition/{token}` → choix du relais (partiels `checkout/_relay-picker` + `checkout/_relay-map`, partagés avec le checkout) puis paiement Stripe ou PayPal (`ReshipmentController`)
- `ReshipmentService::confirmPayment()` (webhook Stripe, page succès, capture PayPal) : remet la commande en `processing` avec le nouveau relais, efface étiquette/suivi Boxtal et `shipped_at`, ajoute une ligne à `customer_note`, envoie `ReshipmentPaid` + `ReshipmentPaidAdmin` + push ; l'admin recrée ensuite l'étiquette Boxtal normalement
- Le montant réglé n'est pas ajouté au total de la commande ni à la facture
