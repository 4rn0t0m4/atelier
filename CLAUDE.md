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
