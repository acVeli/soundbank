<?php

use App\Http\Controllers\Account\BecomeArtistController;
use App\Http\Controllers\Account\DashboardController as AccountDashboardController;
use App\Http\Controllers\Account\FavoriteController;
use App\Http\Controllers\Account\LibraryController;
use App\Http\Controllers\Account\OrderController as AccountOrderController;
use App\Http\Controllers\Account\ProfileController as AccountProfileController;
use App\Http\Controllers\Admin\ArtistController as AdminArtistController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PayoutController as AdminPayoutController;
use App\Http\Controllers\Admin\SampleController as AdminSampleController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PackController;
use App\Http\Controllers\SampleController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\Studio\DashboardController as StudioDashboardController;
use App\Http\Controllers\Studio\PackController as StudioPackController;
use App\Http\Controllers\Studio\PayoutController as StudioPayoutController;
use App\Http\Controllers\Studio\ProfileController as StudioProfileController;
use App\Http\Controllers\Studio\SaleController as StudioSaleController;
use App\Http\Controllers\Studio\SampleController as StudioSampleController;
use Illuminate\Support\Facades\Route;

/*
| Vitrine et catalogue public.
*/
Route::get('/', HomeController::class)->name('home');
Route::get('/explorer', [ExploreController::class, 'index'])->name('explore');
Route::get('/explorer/{category}', [ExploreController::class, 'index'])->name('explore.category');
Route::get('/sons/{sample}', [SampleController::class, 'show'])->name('samples.show');
Route::post('/sons/{sample}/ecoute', [SampleController::class, 'play'])
    ->middleware('throttle:60,1')
    ->name('samples.play');
Route::get('/packs', [PackController::class, 'index'])->name('packs.index');
Route::get('/packs/{pack}', [PackController::class, 'show'])->name('packs.show');
Route::get('/artistes', [ArtistController::class, 'index'])->name('artists.index');
Route::get('/artistes/{artistProfile}', [ArtistController::class, 'show'])->name('artists.show');

Route::get('/panier', [CartController::class, 'index'])->name('cart');
Route::post('/panier/{type}/{id}', [CartController::class, 'add'])
    ->whereIn('type', ['sample', 'pack'])->whereNumber('id')
    ->name('cart.add');
Route::delete('/panier/{type}/{id}', [CartController::class, 'remove'])
    ->whereIn('type', ['sample', 'pack'])->whereNumber('id')
    ->name('cart.remove');

/*
| Authentification : deux parcours d'inscription (acheteur / artiste).
*/
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [AuthController::class, 'login'])->name('login');
    Route::post('/connexion', [AuthController::class, 'authenticate'])->middleware('throttle:6,1')->name('login.store');
    Route::get('/inscription', [AuthController::class, 'register'])->name('register');
    Route::post('/inscription', [AuthController::class, 'storeBuyer'])->middleware('throttle:6,1')->name('register.store');
    Route::get('/inscription/artiste', [AuthController::class, 'registerArtist'])->name('register.artist');
    Route::post('/inscription/artiste', [AuthController::class, 'storeArtist'])->middleware('throttle:6,1')->name('register.artist.store');
    Route::get('/mot-de-passe-oublie', [PasswordController::class, 'request'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [PasswordController::class, 'email'])->middleware('throttle:5,10')->name('password.email');
    Route::get('/reinitialiser/{token}', [PasswordController::class, 'reset'])->name('password.reset');
    Route::post('/reinitialiser', [PasswordController::class, 'update'])->middleware('throttle:6,1')->name('password.update');
});
Route::post('/deconnexion', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
| Espace acheteur (ouvert à tout compte connecté).
*/
Route::middleware('auth')->group(function () {
    Route::get('/commande', [CheckoutController::class, 'show'])->name('checkout');
    Route::post('/commande/{provider}', [CheckoutController::class, 'store'])
        ->whereIn('provider', ['stripe', 'paypal'])
        ->middleware('throttle:10,1')
        ->name('checkout.store');
    Route::get('/commande/{order}/stripe/retour', [CheckoutController::class, 'stripeReturn'])->name('checkout.stripe.return');
    Route::get('/commande/{order}/paypal/retour', [CheckoutController::class, 'paypalReturn'])->name('checkout.paypal.return');
    Route::get('/commande/{order}/annulee', [CheckoutController::class, 'cancelled'])->name('checkout.cancelled');
    Route::get('/commande/{order}/merci', [CheckoutController::class, 'success'])->name('checkout.success');

    Route::post('/favoris/{sample}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    Route::prefix('/compte')->name('account.')->group(function () {
        Route::get('/', AccountDashboardController::class)->name('dashboard');
        Route::get('/bibliotheque', [LibraryController::class, 'index'])->name('library');
        Route::get('/bibliotheque/{libraryItem}/telecharger', [LibraryController::class, 'download'])
            ->middleware('throttle:120,1')
            ->name('library.download');
        Route::get('/bibliotheque/{libraryItem}/licence', [LibraryController::class, 'license'])->name('library.license');
        Route::get('/commandes', [AccountOrderController::class, 'index'])->name('orders');
        Route::get('/commandes/{order}', [AccountOrderController::class, 'show'])->name('orders.show');
        Route::get('/commandes/{order}/zip', [AccountOrderController::class, 'zip'])
            ->middleware('throttle:20,1')
            ->name('orders.zip');
        Route::get('/favoris', [FavoriteController::class, 'index'])->name('favorites');
        Route::get('/profil', [AccountProfileController::class, 'edit'])->name('profile');
        Route::put('/profil', [AccountProfileController::class, 'update'])->name('profile.update');
        Route::put('/mot-de-passe', [AccountProfileController::class, 'password'])->name('password');
        Route::get('/devenir-artiste', [BecomeArtistController::class, 'create'])->name('become-artist');
        Route::post('/devenir-artiste', [BecomeArtistController::class, 'store'])->name('become-artist.store');
    });
});

/*
| Studio artiste.
*/
Route::prefix('/studio')->name('studio.')->middleware(['auth', 'artist'])->group(function () {
    Route::get('/', StudioDashboardController::class)->name('dashboard');

    Route::get('/sons', [StudioSampleController::class, 'index'])->name('samples.index');
    Route::get('/sons/importer', [StudioSampleController::class, 'create'])->name('samples.create');
    Route::post('/sons', [StudioSampleController::class, 'store'])->middleware('throttle:120,1')->name('samples.store');
    Route::post('/sons/lot', [StudioSampleController::class, 'bulk'])->name('samples.bulk');
    Route::get('/sons/{sample}/modifier', [StudioSampleController::class, 'edit'])->name('samples.edit');
    Route::put('/sons/{sample}', [StudioSampleController::class, 'update'])->name('samples.update');
    Route::delete('/sons/{sample}', [StudioSampleController::class, 'destroy'])->name('samples.destroy');

    Route::get('/catalogue', [StudioPackController::class, 'index'])->name('packs.index');
    Route::get('/catalogue/nouveau', [StudioPackController::class, 'create'])->name('packs.create');
    Route::post('/catalogue', [StudioPackController::class, 'store'])->name('packs.store');
    Route::get('/catalogue/{pack}/modifier', [StudioPackController::class, 'edit'])->name('packs.edit');
    Route::put('/catalogue/{pack}', [StudioPackController::class, 'update'])->name('packs.update');
    Route::delete('/catalogue/{pack}', [StudioPackController::class, 'destroy'])->name('packs.destroy');

    Route::get('/ventes', [StudioSaleController::class, 'index'])->name('sales');
    Route::get('/versements', [StudioPayoutController::class, 'index'])->name('payouts');
    Route::post('/versements', [StudioPayoutController::class, 'store'])->middleware('throttle:5,1')->name('payouts.store');
    Route::get('/profil', [StudioProfileController::class, 'edit'])->name('profile');
    Route::put('/profil', [StudioProfileController::class, 'update'])->name('profile.update');
});

/*
| Paiements et factures.
*/
Route::post('/webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');
Route::get('/factures/{token}', [InvoiceController::class, 'show'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->name('invoices.show');

/*
| Administration.
*/
Route::prefix('/admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::get('/commandes', [AdminOrderController::class, 'index'])->name('orders');
    Route::get('/commandes/{order}', [AdminOrderController::class, 'show'])->name('orders.show');

    Route::get('/factures', [AdminInvoiceController::class, 'index'])->name('invoices');
    Route::get('/factures/export', [AdminInvoiceController::class, 'export'])->name('invoices.export');
    Route::get('/factures/{invoice}', [AdminInvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/factures/{invoice}/envoi', [AdminInvoiceController::class, 'send'])->middleware('throttle:20,1')->name('invoices.send');
    Route::get('/factures/{invoice}/corriger', [AdminInvoiceController::class, 'editCorrection'])->name('invoices.correct');
    Route::post('/factures/{invoice}/corriger', [AdminInvoiceController::class, 'storeCorrection'])->name('invoices.correct.store');

    Route::get('/artistes', [AdminArtistController::class, 'index'])->name('artists');
    Route::patch('/artistes/{user}/verification', [AdminArtistController::class, 'verify'])->name('artists.verify');
    Route::patch('/artistes/{user}/statut', [AdminArtistController::class, 'status'])->name('artists.status');

    Route::get('/sons', [AdminSampleController::class, 'index'])->name('samples');
    Route::patch('/sons/{sample}/statut', [AdminSampleController::class, 'status'])->name('samples.status');

    Route::get('/versements', [AdminPayoutController::class, 'index'])->name('payouts');
    Route::patch('/versements/{payout}', [AdminPayoutController::class, 'update'])->name('payouts.update');

    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/reglages', [AdminSettingsController::class, 'edit'])->name('settings');
    Route::put('/reglages', [AdminSettingsController::class, 'update'])->name('settings.update');
    Route::post('/reglages/test/{provider}', [AdminSettingsController::class, 'test'])
        ->whereIn('provider', ['stripe', 'paypal'])
        ->middleware('throttle:10,1')
        ->name('settings.test');
});
