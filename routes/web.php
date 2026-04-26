<?php

use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DiscountCodeController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\QuoteRequestController as AdminQuoteRequestController;
use App\Http\Controllers\Admin\SalesByCountryReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShippingRateController;
use App\Http\Controllers\Auth\AdminSessionController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderLookupController;
use App\Http\Controllers\QuoteRequestController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/contatti', [StorefrontController::class, 'contatti'])->name('contatti');
Route::get('/cerca', [StorefrontController::class, 'search'])->name('search');
Route::get('/preventivo', [QuoteRequestController::class, 'create'])->name('quote.create');
Route::post('/preventivo', [QuoteRequestController::class, 'store'])->middleware(['honeypot', 'throttle:quote-requests'])->name('quote.store');
Route::get('/shop', [StorefrontController::class, 'collection'])->name('shop.index');
Route::get('/collezioni', [StorefrontController::class, 'collections'])->name('collections.index');
Route::get('/collezioni/{slug}', [StorefrontController::class, 'collection'])->name('collections.show');
Route::get('/prodotti/{product:slug}', [StorefrontController::class, 'product'])->name('products.show');

Route::get('/carrello', [CartController::class, 'show'])->name('cart.show');
Route::post('/carrello/{product:slug}', [CartController::class, 'add'])->middleware('throttle:cart-actions')->name('cart.add');
Route::delete('/carrello/{itemId}', [CartController::class, 'remove'])->middleware('throttle:cart-actions')->name('cart.remove');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout/coupon', [CheckoutController::class, 'applyCoupon'])->middleware('throttle:checkout-coupon')->name('checkout.coupon.apply');
Route::post('/checkout/coupon/remove', [CheckoutController::class, 'removeCoupon'])->middleware('throttle:checkout-coupon')->name('checkout.coupon.remove');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware(['honeypot', 'throttle:checkout-submit'])->name('checkout.store');
Route::get('/checkout/paga/{order:number}', [CheckoutController::class, 'pay'])->name('checkout.pay');
Route::get('/checkout/grazie/{order:number}', [CheckoutController::class, 'thankYou'])->name('checkout.thank-you');
Route::get('/ordine', [OrderLookupController::class, 'create'])->name('orders.lookup');
Route::post('/ordine', [OrderLookupController::class, 'show'])->middleware(['honeypot', 'throttle:order-lookup'])->name('orders.lookup.show');
Route::post('/webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');

Route::get('/admin/login', [AdminSessionController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminSessionController::class, 'store'])->middleware(['honeypot', 'throttle:admin-login'])->name('admin.login.store');

Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
    Route::post('logout', [AdminSessionController::class, 'destroy'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('categories', CategoryController::class)->except('show');
    Route::resource('products', ProductController::class);
    Route::resource('shipping-rates', ShippingRateController::class)->except('show');
    Route::resource('discounts', DiscountCodeController::class)->except('show');
    Route::get('reports/sales-by-country', [SalesByCountryReportController::class, 'index'])->name('reports.sales-by-country');
    Route::get('reports/sales-by-country/export', [SalesByCountryReportController::class, 'export'])->name('reports.sales-by-country.export');
    Route::get('quote-requests', [AdminQuoteRequestController::class, 'index'])->name('quote-requests.index');
    Route::get('quote-requests/{quoteRequest}', [AdminQuoteRequestController::class, 'show'])->name('quote-requests.show');
    Route::patch('quote-requests/{quoteRequest}', [AdminQuoteRequestController::class, 'update'])->name('quote-requests.update');
    Route::get('quote-requests/{quoteRequest}/artwork/download', [AdminQuoteRequestController::class, 'downloadArtwork'])->name('quote-requests.artwork.download');
    Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::patch('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/export', [OrderController::class, 'export'])->name('orders.export');
    Route::get('orders/{order:number}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order:number}', [OrderController::class, 'update'])->name('orders.update');
    Route::post('orders/{order:number}/refund', [OrderController::class, 'refund'])->name('orders.refund');
    Route::get('orders/{order:number}/print-files/{printFile}/download', [OrderController::class, 'downloadPrintFile'])->name('orders.print-files.download');
    Route::patch('orders/{order:number}/fulfill', [OrderController::class, 'fulfill'])->name('orders.fulfill');
    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
});
