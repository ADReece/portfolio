<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminOrdersController;
use App\Http\Controllers\Admin\AdminPrintCatalogueController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminSubscriptionsController;
use App\Http\Controllers\Admin\AdminUsersController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\CustomDomainController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DigitalDownloadController;
use App\Http\Controllers\DigitalSalesSettingsController;
use App\Http\Controllers\DownloadPurchaseController;
use App\Http\Controllers\FontController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\PrintPurchaseController;
use App\Http\Controllers\ProdigiProductSettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SetController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\WebhookController;
use App\Livewire\TemplateBatch;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Controllers\PaymentController as CashierPaymentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Cheap health check for the container: it touches no database.
Route::get('/up', fn () => response('ok', 200, ['Content-Type' => 'text/plain']))->name('health');

// Per-host robots.txt and sitemap (main site or a photographer's own domain)
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');

// Public homepage
Route::get('/', [HomeController::class, 'index'])->name('home');

// Legal pages
Route::get('/terms', [HomeController::class, 'terms'])->name('terms');
Route::get('/privacy', [HomeController::class, 'privacy'])->name('privacy');
Route::get('/sla', [HomeController::class, 'sla'])->name('sla');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::view('/photo-credits', 'legal.photo-credits')->name('photo-credits');
Route::get('/examples/{slug}', [HomeController::class, 'example'])
    ->whereIn('slug', array_keys(config('marketing.showcases')))->name('examples.show');

// Pricing
Route::get('/pricing', [BillingController::class, 'pricing'])->name('pricing');

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

// Backend Routes
Route::middleware('auth')->group(function () {
    // Billing routes
    Route::get('/checkout/{plan}', [BillingController::class, 'checkout'])->name('checkout');
    Route::post('/checkout/{plan}', [BillingController::class, 'processCheckout'])->name('checkout.process');
    Route::get('/billing/portal', [BillingController::class, 'billingPortal'])->name('billing.portal');
    Route::post('/subscription/cancel', [BillingController::class, 'cancel'])->name('subscription.cancel');
    Route::post('/subscription/resume', [BillingController::class, 'resume'])->name('subscription.resume');
    Route::post('/subscription/swap/{plan}', [BillingController::class, 'swap'])->name('subscription.swap');
    Route::get('/billing/invoices/{invoiceId}', [BillingController::class, 'downloadInvoice'])->name('billing.invoice.download');

    // Stripe Connect routes
    Route::get('/connect/stripe', [BillingController::class, 'connectStripe'])->name('connect.stripe');
    Route::get('/connect/stripe/callback', [BillingController::class, 'handleConnectCallback'])->name('connect.stripe.callback');
    Route::post('/connect/stripe/disconnect', [BillingController::class, 'disconnectStripe'])->name('connect.stripe.disconnect');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/display', [ProfileController::class, 'updateDisplayMode'])->name('profile.update-display');
    Route::patch('/profile/watermark', [ProfileController::class, 'updateWatermark'])->name('profile.watermark.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/upload-files', [PhotoController::class, 'upload'])->name('upload-files')->middleware('photo.limit');

    Route::group(['prefix' => 'collections'], function () {
        Route::get('/', [CollectionController::class, 'index'])->name('collections.index');
        Route::get('/create', [CollectionController::class, 'create'])->name('collections.create')->middleware('collection.limit');
        Route::post('/create', [CollectionController::class, 'store'])->name('collections.store')->middleware('collection.limit');
        Route::get('/{collection}/edit', [CollectionController::class, 'edit'])->name('collections.edit');
        Route::patch('/{collection}', [CollectionController::class, 'update'])->name('collections.update');
        Route::delete('/{collection}', [CollectionController::class, 'destroy'])->name('collections.destroy');

        Route::get('/{collection}/sets', [CollectionController::class, 'sets'])->name('sets.create');
    });

    // Sets routes
    Route::get('/sets/{set}', [SetController::class, 'show'])->name('sets.detail');
    Route::post('/collections/{collection}/sets', [SetController::class, 'store'])->name('sets.store');
    Route::patch('/collections/{collection}/sets/{set}', [SetController::class, 'update'])->name('sets.update');
    Route::delete('/collections/{collection}/sets/{set}', [SetController::class, 'destroy'])->name('sets.destroy');
    Route::post('/sets/{set}/templates', [SetController::class, 'syncTemplates'])->name('sets.templates.sync');

    Route::post('/collections/{collection}/email-client', [CollectionController::class, 'emailClient'])->name('collections.email-client');

    // Photo management
    Route::group(['prefix' => 'photos'], function () {
        Route::patch('/{photo}', [PhotoController::class, 'update'])->name('photos.update');
        Route::delete('/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');
        Route::post('/bulk-delete', [PhotoController::class, 'bulkDelete'])->name('photos.bulk-delete');
        Route::post('/{photo}/move', [PhotoController::class, 'move'])->name('photos.move');
    });

    Route::delete('/profile/logo', [ProfileController::class, 'removeLogo'])->name('profile.logo.remove');

    Route::get('/profile/downloads', [DigitalSalesSettingsController::class, 'edit'])->name('profile.digital-sales.edit');
    Route::patch('/profile/downloads', [DigitalSalesSettingsController::class, 'update'])->name('profile.digital-sales.update');
    Route::patch('/collections/{collection}/downloads', [DigitalSalesSettingsController::class, 'updateCollection'])->name('collections.digital-sales.update');
    Route::get('/profile/print-products', [ProdigiProductSettingsController::class, 'edit'])->name('profile.prodigi-products.edit');
    Route::patch('/profile/print-products', [ProdigiProductSettingsController::class, 'update'])->name('profile.prodigi-products.update');
    // Template CRUD
    Route::resource('templates', TemplateController::class)->except(['show']);

    // Template test (single-photo preview)
    Route::get('/templates/{template}/test', [TemplateController::class, 'test'])->name('templates.test');
    Route::post('/templates/{template}/test', [TemplateController::class, 'runTest'])->name('templates.runTest');
    Route::get('/templates/{template}/test/{generatedPrint}', [TemplateController::class, 'testResult'])->name('templates.testResult');

    // Font management
    Route::get('/font-library', [FontController::class, 'index'])->name('fonts.index');
    Route::post('/font-library', [FontController::class, 'store'])->name('fonts.store');
    Route::delete('/font-library/{font}', [FontController::class, 'destroy'])->name('fonts.destroy');

    // Template batch — Livewire full-page component
    Route::get('/collections/{collectionId}/batch', TemplateBatch::class)->name('collections.batch');
});

// Custom domains (e.g. stephshoots.com); plan limits in config/custom_domains.php
Route::middleware('auth')->prefix('/settings/domain')->group(function () {
    Route::get('/', [CustomDomainController::class, 'index'])->name('custom-domains.index');
    Route::post('/', [CustomDomainController::class, 'store'])->middleware('throttle:10,1')->name('custom-domains.store');
    Route::patch('/{domain}', [CustomDomainController::class, 'update'])->name('custom-domains.update');
    Route::post('/{domain}/verify', [CustomDomainController::class, 'verify'])->name('custom-domains.verify');
    Route::delete('/{domain}', [CustomDomainController::class, 'destroy'])->name('custom-domains.destroy');
});

Route::middleware(['auth', 'subscription:upload_logo'])->group(function () {
    Route::get('/profile/customize', [ProfileController::class, 'customize'])->name('profile.customize');
    Route::patch('/profile/customize', [ProfileController::class, 'updateCustomization'])->name('profile.customize.update');
});

// Public facing Routes
Route::prefix('/@{username}')->group(function () {
    Route::get('/', [ProfileController::class, 'view'])->name('profile.view');

    Route::get('/collections', [ProfileController::class, 'collections'])->name('profile.collections');
    Route::get('/collections/{collection_id}', [ProfileController::class, 'collection'])->name('profile.collection');
    Route::post('/collections/{collection_id}/unlock', [ProfileController::class, 'unlockCollectionWithPassword'])
        ->middleware('throttle:10,1')
        ->name('profile.collection.unlock');
    Route::get('/print-products', [ProdigiProductSettingsController::class, 'listForPortfolio'])->name('profile.print-products');
    // Credits for stock photos (the demo portfolio's Unsplash photos).
    Route::get('/photo-credits', function (string $username) {
        $user = \App\Models\User::where('username', $username)->firstOrFail();
        $photos = $user->photos()->whereNotNull('credit')->where('private', false)->ready()->orderBy('created_at')->get();
        abort_if($photos->isEmpty(), 404);

        return view('demo.credits', ['user' => $user, 'photos' => $photos]);
    })->name('profile.photo-credits');
});

// Client "download all" archives. Clients don't have accounts: requesting an
// archive needs an unlocked gallery, and the emailed links are signed.
Route::post('/collections/{collection}/request-archive', [CollectionController::class, 'requestArchive'])
    ->middleware('throttle:5,1')
    ->name('collections.request-archive');
Route::get('/collections/{collection}/archive/{filename}', [CollectionController::class, 'archiveDownloadPage'])
    ->middleware('signed')
    ->name('collections.archive-download-page');
Route::get('/collections/{collection}/download-archive/{filename}', [CollectionController::class, 'downloadArchive'])
    ->middleware('signed')
    ->name('collections.download-archive');

// Buying full-resolution downloads from a collection, and the buyer's
// download links (signed, from the delivery email).
Route::get('/collections/{collection}/buy', [DownloadPurchaseController::class, 'show'])->name('download-purchase.show');
Route::post('/collections/{collection}/buy', [DownloadPurchaseController::class, 'store'])->middleware('throttle:10,1')->name('download-purchase.store');
Route::get('/orders/{order}/downloads-paid', [DownloadPurchaseController::class, 'success'])->name('download-purchase.success');
Route::get('/orders/{order}/downloads-cancelled', [DownloadPurchaseController::class, 'cancel'])->name('download-purchase.cancel');
Route::middleware('signed')->group(function () {
    Route::get('/downloads/{order}', [DigitalDownloadController::class, 'show'])->name('downloads.show');
    Route::get('/downloads/{order}/photos/{photo}', [DigitalDownloadController::class, 'photo'])->name('downloads.photo');
    Route::get('/downloads/{order}/all.zip', [DigitalDownloadController::class, 'zip'])->name('downloads.zip');
});

// Public photo download and purchase requests
Route::get('/photos/{photo}/download-options', [PhotoController::class, 'downloadOptions'])->name('photos.download-options');
Route::post('/photos/{photo}/download-with-template/{template}', [PhotoController::class, 'downloadWithTemplate'])->name('photos.download-with-template');
Route::get('/photos/{photo}/download-result/{generatedPrint}', [PhotoController::class, 'downloadResult'])->name('photos.download-result');
Route::post('/photos/{photo}/request-download', [PhotoController::class, 'requestDownload'])->middleware('throttle:5,1')->name('photos.request-download');
Route::post('/photos/{photo}/request-purchase', [PhotoController::class, 'requestPurchase'])->middleware('throttle:5,1')->name('photos.request-purchase');
Route::get('/photos/{photo}/buy-print', [PrintPurchaseController::class, 'show'])->name('print-purchase.show');
Route::post('/photos/{photo}/buy-print', [PrintPurchaseController::class, 'store'])->name('print-purchase.store');
Route::get('/orders/{order}/purchase-success', [PrintPurchaseController::class, 'success'])->name('print-purchase.success');
Route::get('/orders/{order}/purchase-cancel', [PrintPurchaseController::class, 'cancel'])->name('print-purchase.cancel');

// Stripe Webhooks
Route::post('/stripe/webhook', [WebhookController::class, 'handleWebhook'])->name('cashier.webhook');

// Cashier payment confirmation (SCA) route
Route::get('/stripe/payment/{id}', [CashierPaymentController::class, 'show'])
    ->name('cashier.payment');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [AdminUsersController::class, 'index'])->name('users.index');
    Route::post('/users/{user}/toggle-override', [AdminUsersController::class, 'toggleOverride'])->name('users.toggle-override');
    Route::post('/users/{user}/override-expiry', [AdminUsersController::class, 'setOverrideExpiry'])->name('users.override-expiry');
    Route::post('/users/{user}/toggle-admin', [AdminUsersController::class, 'toggleAdmin'])->name('users.toggle-admin');
    Route::post('/users/{user}/impersonate', [AdminUsersController::class, 'impersonate'])->name('users.impersonate');

    Route::get('/subscriptions', [AdminSubscriptionsController::class, 'index'])->name('subscriptions.index');
    Route::get('/orders', [AdminOrdersController::class, 'index'])->name('orders.index');

    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');

    // Print catalogue (Prodigi products Portfolio offers)
    Route::get('/print-catalogue', [AdminPrintCatalogueController::class, 'index'])->name('print-catalogue.index');
    Route::get('/print-catalogue/create', [AdminPrintCatalogueController::class, 'create'])->name('print-catalogue.create');
    Route::post('/print-catalogue/lookup', [AdminPrintCatalogueController::class, 'lookup'])->middleware('throttle:30,1')->name('print-catalogue.lookup');
    Route::post('/print-catalogue', [AdminPrintCatalogueController::class, 'store'])->name('print-catalogue.store');
    Route::post('/print-catalogue/bulk', [AdminPrintCatalogueController::class, 'storeMany'])->middleware('throttle:10,1')->name('print-catalogue.store-many');
    Route::post('/print-catalogue/import', [AdminPrintCatalogueController::class, 'import'])->middleware('throttle:10,1')->name('print-catalogue.import');
    Route::post('/print-catalogue/discover', [AdminPrintCatalogueController::class, 'discover'])->middleware('throttle:5,1')->name('print-catalogue.discover');
    Route::post('/print-catalogue/bulk-status', [App\Http\Controllers\Admin\AdminPrintCatalogueController::class, 'bulkStatus'])->name('print-catalogue.bulk-status');
    Route::post('/print-catalogue/group-image', [App\Http\Controllers\Admin\AdminPrintCatalogueController::class, 'groupImage'])->name('print-catalogue.group-image');
    Route::post('/print-catalogue/mockups', [App\Http\Controllers\Admin\AdminPrintCatalogueController::class, 'mockups'])->middleware('throttle:5,1')->name('print-catalogue.mockups');
    Route::post('/print-catalogue/refresh-all', [AdminPrintCatalogueController::class, 'refreshAll'])->middleware('throttle:5,1')->name('print-catalogue.refresh-all');
    Route::get('/print-catalogue/{product}/edit', [AdminPrintCatalogueController::class, 'edit'])->name('print-catalogue.edit');
    Route::put('/print-catalogue/{product}', [AdminPrintCatalogueController::class, 'update'])->name('print-catalogue.update');
    Route::post('/print-catalogue/{product}/refresh', [AdminPrintCatalogueController::class, 'refresh'])->middleware('throttle:30,1')->name('print-catalogue.refresh');
    Route::post('/print-catalogue/{product}/toggle', [AdminPrintCatalogueController::class, 'toggle'])->name('print-catalogue.toggle');

    // Subscription Plan CRUD
    Route::get('/plans', [AdminSettingsController::class, 'plansIndex'])->name('plans.index');
    Route::get('/plans/create', [AdminSettingsController::class, 'plansCreate'])->name('plans.create');
    Route::post('/plans', [AdminSettingsController::class, 'plansStore'])->name('plans.store');
    Route::get('/plans/{plan}/edit', [AdminSettingsController::class, 'plansEdit'])->name('plans.edit');
    Route::put('/plans/{plan}', [AdminSettingsController::class, 'plansUpdate'])->name('plans.update');
    Route::delete('/plans/{plan}', [AdminSettingsController::class, 'plansDestroy'])->name('plans.destroy');
});

// Route for stopping impersonation (available to all authenticated users)
Route::post('/admin/stop-impersonating', [AdminUsersController::class, 'stopImpersonating'])
    ->middleware('auth')
    ->name('admin.users.stop-impersonating');

// Component reference page for developers (local only).
if (app()->environment('local')) {
    Route::view('/dev/ui', 'dev.ui')->middleware('auth')->name('dev.ui');
}

require __DIR__.'/auth.php';
