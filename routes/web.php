<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoxtalController;
use App\Http\Controllers\BoxtalWebhookController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\PayPalController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegacyRedirectController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

// Accueil
Route::get('/', [HomeController::class, 'index'])->name('home');

// Boutique
Route::get('/boutique', [ShopController::class, 'index'])->name('shop.index');
Route::get('/boutique/recherche', [ShopController::class, 'search'])->name('shop.search');
Route::post('/boutique/{product}/avis', [ShopController::class, 'storeReview'])->name('shop.review.store')->middleware('throttle:5,1');
Route::get('/boutique/{parent}/{child}/{product}', [ShopController::class, 'show'])->name('shop.show');
Route::get('/boutique/{parent}/{child?}', [ShopController::class, 'categoryOrProduct'])->name('shop.category');

// Panier
Route::get('/panier', [CartController::class, 'index'])->name('cart.index');
Route::get('/panier/mini', [CartController::class, 'miniCart'])->name('cart.mini');
Route::post('/panier/ajouter', [CartController::class, 'add'])->name('cart.add');
Route::patch('/panier/{key}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/panier/{key}', [CartController::class, 'remove'])->name('cart.remove');

// Commande (checkout)
Route::get('/commande', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/commande', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('throttle:10,1');
Route::get('/commande/succes', [CheckoutController::class, 'success'])->name('checkout.success');

// Stripe webhook
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);

// Boxtal (points relais + webhook)
Route::get('/api/boxtal/map-token', [BoxtalController::class, 'mapToken'])->name('boxtal.map-token');
Route::post('/api/boxtal/parcel-points', [BoxtalController::class, 'searchParcelPoints'])->name('boxtal.parcel-points');
Route::post('/api/boxtal/webhook', [BoxtalWebhookController::class, 'handle'])->name('boxtal.webhook');

// PayPal
Route::post('/api/paypal/create-order', [PayPalController::class, 'createOrder'])->name('paypal.create-order');
Route::post('/api/paypal/capture-order', [PayPalController::class, 'captureOrder'])->name('paypal.capture-order');

// Authentification (guest)
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/connexion', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:5,1');
    Route::get('/inscription', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/inscription', [AuthController::class, 'register'])->name('register.post')->middleware('throttle:5,1');
    Route::get('/mot-de-passe-oublie', [AuthController::class, 'forgotPasswordForm'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [AuthController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:3,1');
    Route::get('/reinitialiser-mot-de-passe/{token}', [AuthController::class, 'resetPasswordForm'])->name('password.reset');
    Route::post('/reinitialiser-mot-de-passe', [AuthController::class, 'resetPassword'])->name('password.update')->middleware('throttle:3,1');
});

// Déconnexion
Route::post('/deconnexion', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Espace client
Route::prefix('/mon-compte')->name('account.')->middleware('auth')->group(function () {
    Route::get('/', [AccountController::class, 'index'])->name('index');
    Route::get('/commandes', [AccountController::class, 'orders'])->name('orders');
    Route::get('/commandes/{order}', [AccountController::class, 'order'])->name('order');
    Route::get('/profil', [AccountController::class, 'editProfile'])->name('profile');
    Route::patch('/profil', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::patch('/mot-de-passe', [AccountController::class, 'updatePassword'])->name('password.update');
    Route::get('/coordonnees', [AccountController::class, 'editAddress'])->name('address');
    Route::patch('/coordonnees', [AccountController::class, 'updateAddress'])->name('address.update');
});

// Contact
Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'send'])->name('contact.send')->middleware('throttle:5,1');

// Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index']);

// Redirections legacy WP (301)
Route::get('/produit/{slug}', [LegacyRedirectController::class, 'product']);
Route::get('/categorie-produit/{slug}', [LegacyRedirectController::class, 'category']);
Route::redirect('/shop', '/boutique', 301);
Route::redirect('/mon-compte', '/connexion', 301);
Route::redirect('/panier-2', '/panier', 301);
Route::redirect('/commande-2', '/commande', 301);

// Pages statiques (wildcard — en dernier)
Route::get('/{slug}', [PageController::class, 'show'])->name('page.show')
    ->where('slug', '^(?!boutique|panier|commande|connexion|inscription|deconnexion|mon-compte|stripe|paypal|admin|api|contact|produit|categorie-produit)[a-z0-9-]+(/[a-z0-9-]+)*$');
