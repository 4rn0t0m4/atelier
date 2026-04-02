<?php

use App\Http\Controllers\Admin\AddonGroupController;
use App\Http\Controllers\Admin\BoxtalSubscriptionController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StatsController;
use App\Http\Controllers\Admin\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Prefix: /admin
| Middleware: web, auth, admin
|
*/

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

// Statistiques
Route::get('statistiques', [StatsController::class, 'index'])->name('admin.stats.index');

// Commandes
Route::get('commandes', [OrderController::class, 'index'])->name('admin.orders.index');
Route::get('commandes/{order}', [OrderController::class, 'show'])->name('admin.orders.show');
Route::get('commandes/{order}/modifier', [OrderController::class, 'edit'])->name('admin.orders.edit');
Route::put('commandes/{order}', [OrderController::class, 'update'])->name('admin.orders.update');
Route::get('commandes/{order}/facture', [OrderController::class, 'invoice'])->name('admin.orders.invoice');
Route::delete('commandes/{order}', [OrderController::class, 'destroy'])->name('admin.orders.destroy');
Route::post('commandes/{order}/create-shipment', [OrderController::class, 'createShipment'])->name('admin.orders.create-shipment');
Route::get('commandes/{order}/label', [OrderController::class, 'label'])->name('admin.orders.label');
Route::delete('commandes/{order}/reset-shipment', [OrderController::class, 'resetShipment'])->name('admin.orders.reset-shipment');

// Réglages
Route::get('reglages', [SettingController::class, 'index'])->name('admin.settings.index');
Route::put('reglages', [SettingController::class, 'update'])->name('admin.settings.update');

// Boxtal webhooks
Route::get('boxtal-subscriptions', [BoxtalSubscriptionController::class, 'index'])->name('admin.boxtal-subscriptions.index');
Route::post('boxtal-subscriptions', [BoxtalSubscriptionController::class, 'store'])->name('admin.boxtal-subscriptions.store');
Route::delete('boxtal-subscriptions/{id}', [BoxtalSubscriptionController::class, 'destroy'])->name('admin.boxtal-subscriptions.destroy');

// Medias
Route::post('media/upload', [MediaController::class, 'upload'])->name('admin.media.upload');

// Produits
Route::get('produits', [ProductController::class, 'index'])->name('admin.products.index');
Route::get('produits/nouveau', [ProductController::class, 'create'])->name('admin.products.create');
Route::post('produits', [ProductController::class, 'store'])->name('admin.products.store');
Route::get('produits/{product}/modifier', [ProductController::class, 'edit'])->name('admin.products.edit');
Route::put('produits/{product}', [ProductController::class, 'update'])->name('admin.products.update');
Route::post('produits/{product}/toggle-active', [ProductController::class, 'toggleActive'])->name('admin.products.toggle-active');
Route::delete('produits/{product}', [ProductController::class, 'destroy'])->name('admin.products.destroy');

// Options produit (addon groups)
Route::get('options-produit', [AddonGroupController::class, 'index'])->name('admin.addon-groups.index');
Route::get('options-produit/nouveau', [AddonGroupController::class, 'create'])->name('admin.addon-groups.create');
Route::post('options-produit', [AddonGroupController::class, 'store'])->name('admin.addon-groups.store');
Route::get('options-produit/{addonGroup}/modifier', [AddonGroupController::class, 'edit'])->name('admin.addon-groups.edit');
Route::put('options-produit/{addonGroup}', [AddonGroupController::class, 'update'])->name('admin.addon-groups.update');
Route::post('options-produit/{addonGroup}/dupliquer', [AddonGroupController::class, 'duplicate'])->name('admin.addon-groups.duplicate');
Route::delete('options-produit/{addonGroup}', [AddonGroupController::class, 'destroy'])->name('admin.addon-groups.destroy');

// Catégories
Route::get('categories', [CategoryController::class, 'index'])->name('admin.categories.index');
Route::get('categories/nouvelle', [CategoryController::class, 'create'])->name('admin.categories.create');
Route::post('categories', [CategoryController::class, 'store'])->name('admin.categories.store');
Route::get('categories/{category}/modifier', [CategoryController::class, 'edit'])->name('admin.categories.edit');
Route::put('categories/{category}', [CategoryController::class, 'update'])->name('admin.categories.update');
Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->name('admin.categories.destroy');

// Clients
Route::get('clients', [CustomerController::class, 'index'])->name('admin.customers.index');
Route::get('clients/{user}', [CustomerController::class, 'show'])->name('admin.customers.show');

// Avis
Route::get('avis', [ReviewController::class, 'index'])->name('admin.reviews.index');
Route::patch('avis/{review}/approuver', [ReviewController::class, 'approve'])->name('admin.reviews.approve');
Route::patch('avis/{review}/rejeter', [ReviewController::class, 'reject'])->name('admin.reviews.reject');
Route::delete('avis/{review}', [ReviewController::class, 'destroy'])->name('admin.reviews.destroy');

// Codes promo / Réductions
Route::get('reductions', [DiscountController::class, 'index'])->name('admin.discounts.index');
Route::get('reductions/nouvelle', [DiscountController::class, 'create'])->name('admin.discounts.create');
Route::post('reductions', [DiscountController::class, 'store'])->name('admin.discounts.store');
Route::get('reductions/{discount}/modifier', [DiscountController::class, 'edit'])->name('admin.discounts.edit');
Route::put('reductions/{discount}', [DiscountController::class, 'update'])->name('admin.discounts.update');
Route::delete('reductions/{discount}', [DiscountController::class, 'destroy'])->name('admin.discounts.destroy');

// Pages
Route::get('pages', [PageController::class, 'index'])->name('admin.pages.index');
Route::get('pages/nouvelle', [PageController::class, 'create'])->name('admin.pages.create');
Route::post('pages', [PageController::class, 'store'])->name('admin.pages.store');
Route::get('pages/{page}/modifier', [PageController::class, 'edit'])->name('admin.pages.edit');
Route::put('pages/{page}', [PageController::class, 'update'])->name('admin.pages.update');
Route::delete('pages/{page}', [PageController::class, 'destroy'])->name('admin.pages.destroy');
