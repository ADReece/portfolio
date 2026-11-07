<?php

use App\Http\Controllers\CollectionController;
use App\Http\Controllers\FontController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PrintPurchaseController;
use App\Http\Controllers\ProdigiProductSettingsController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\SetController;
use App\Http\Controllers\TemplateController;
use App\Http\Livewire\ManageSets;
use App\Http\Livewire\PhotoUpload;
use App\Http\Livewire\TemplateBatch;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminOrdersController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminSubscriptionsController;
use App\Http\Controllers\Admin\AdminUsersController;
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

// Public homepage
Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Legal pages
Route::get('/terms', [App\Http\Controllers\HomeController::class, 'terms'])->name('terms');
Route::get('/privacy', [App\Http\Controllers\HomeController::class, 'privacy'])->name('privacy');
Route::get('/sla', [App\Http\Controllers\HomeController::class, 'sla'])->name('sla');
Route::get('/about', [App\Http\Controllers\HomeController::class, 'about'])->name('about');

// Pricing
Route::get('/pricing', [App\Http\Controllers\BillingController::class, 'pricing'])->name('pricing');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


//Backend Routes
Route::middleware('auth')->group(function () {
    // Billing routes
    Route::get('/checkout/{plan}', [App\Http\Controllers\BillingController::class, 'checkout'])->name('checkout');
    Route::post('/checkout/{plan}', [App\Http\Controllers\BillingController::class, 'processCheckout'])->name('checkout.process');
    Route::get('/billing/portal', [App\Http\Controllers\BillingController::class, 'billingPortal'])->name('billing.portal');
    Route::post('/subscription/cancel', [App\Http\Controllers\BillingController::class, 'cancel'])->name('subscription.cancel');
    Route::post('/subscription/resume', [App\Http\Controllers\BillingController::class, 'resume'])->name('subscription.resume');
    Route::post('/subscription/swap/{plan}', [App\Http\Controllers\BillingController::class, 'swap'])->name('subscription.swap');
    Route::get('/billing/invoices/{invoiceId}', [App\Http\Controllers\BillingController::class, 'downloadInvoice'])->name('billing.invoice.download');

    // Stripe Connect routes
    Route::get('/connect/stripe', [App\Http\Controllers\BillingController::class, 'connectStripe'])->name('connect.stripe');
    Route::get('/connect/stripe/callback', [App\Http\Controllers\BillingController::class, 'handleConnectCallback'])->name('connect.stripe.callback');
    Route::post('/connect/stripe/disconnect', [App\Http\Controllers\BillingController::class, 'disconnectStripe'])->name('connect.stripe.disconnect');

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
    Route::post('/collections/{collection}/request-archive', [CollectionController::class, 'requestArchive'])->name('collections.request-archive');
    Route::get('/collections/{collection}/archive/{filename}', [CollectionController::class, 'archiveDownloadPage'])->name('collections.archive-download-page');
    Route::get('/collections/{collection}/download-archive/{filename}', [CollectionController::class, 'downloadArchive'])->name('collections.download-archive');


    // Photo management
    Route::group(['prefix' => 'photos'], function () {
        Route::patch('/{photo}', [PhotoController::class, 'update'])->name('photos.update');
        Route::delete('/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');
        Route::post('/bulk-delete', [PhotoController::class, 'bulkDelete'])->name('photos.bulk-delete');
        Route::post('/{photo}/move', [PhotoController::class, 'move'])->name('photos.move');
    });

    Route::delete('/profile/logo', [ProfileController::class, 'removeLogo'])->name('profile.logo.remove');

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
    Route::get('/collections/{collection}/batch', TemplateBatch::class)->name('collections.batch');
});

Route::middleware(['auth','subscription:upload_logo'])->group(function(){
    Route::get('/profile/customize', [ProfileController::class, 'customize'])->name('profile.customize');
    Route::patch('/profile/customize', [ProfileController::class, 'updateCustomization'])->name('profile.customize.update');
});


//Public facing Routes
Route::prefix('/@{username}')->group(function(){
    Route::get('/', [ProfileController::class, 'view'])->name('profile.view');

    Route::get('/collections', [ProfileController::class, 'collections'])->name('profile.collections');
    Route::get('/collections/{collection_id}', [ProfileController::class, 'collection'])->name('profile.collection');
    Route::post('/collections/{collection_id}/unlock', [ProfileController::class, 'unlockCollectionWithPassword'])
        ->middleware('throttle:10,1')
        ->name('profile.collection.unlock');
    Route::get('/print-products', [ProdigiProductSettingsController::class, 'listForPortfolio'])->name('profile.print-products');
});

// Public photo download and purchase requests
Route::get('/photos/{photo}/download-options', [PhotoController::class, 'downloadOptions'])->name('photos.download-options');
Route::post('/photos/{photo}/download-with-template/{template}', [PhotoController::class, 'downloadWithTemplate'])->name('photos.download-with-template');
Route::get('/photos/{photo}/download-result/{generatedPrint}', [PhotoController::class, 'downloadResult'])->name('photos.download-result');
Route::post('/photos/{photo}/request-download', [PhotoController::class, 'requestDownload'])->name('photos.request-download');
Route::post('/photos/{photo}/request-purchase', [PhotoController::class, 'requestPurchase'])->name('photos.request-purchase');
Route::get('/photos/{photo}/buy-print', [PrintPurchaseController::class, 'show'])->name('print-purchase.show');
Route::post('/photos/{photo}/buy-print', [PrintPurchaseController::class, 'store'])->name('print-purchase.store');
Route::get('/orders/{order}/purchase-success', [PrintPurchaseController::class, 'success'])->name('print-purchase.success');
Route::get('/orders/{order}/purchase-cancel', [PrintPurchaseController::class, 'cancel'])->name('print-purchase.cancel');

// Stripe Webhooks
Route::post('/stripe/webhook', [App\Http\Controllers\WebhookController::class, 'handleWebhook'])->name('cashier.webhook');

// Cashier payment confirmation (SCA) route
Route::get('/stripe/payment/{payment_intent}', [CashierPaymentController::class, 'show'])
    ->name('cashier.payment');

Route::middleware(['auth','admin'])->prefix('admin')->name('admin.')->group(function(){
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

require __DIR__.'/auth.php';
