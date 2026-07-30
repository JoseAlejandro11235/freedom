<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', HomeController::class)->name('home');

Route::get('/catalog', CatalogController::class)->name('catalog');

Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
Route::patch('/cart/items/{product}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('/cart/items/{product}', [CartController::class, 'destroy'])->name('cart.items.destroy');

Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/orders/{order}/pay', [CheckoutController::class, 'fakePay'])->name('checkout.fake.pay');
Route::post('/checkout/orders/{order}/pay', [CheckoutController::class, 'fakeConfirm'])->name('checkout.fake.confirm');
Route::get('/checkout/orders/{order}/culqi', [CheckoutController::class, 'culqiPay'])->name('checkout.culqi.pay');
Route::post('/checkout/orders/{order}/culqi/charge', [CheckoutController::class, 'culqiCharge'])->name('checkout.culqi.charge');
Route::get('/checkout/orders/{order}/success', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/checkout/orders/{order}/pending', [CheckoutController::class, 'pending'])->name('checkout.pending');
Route::get('/checkout/orders/{order}/failure', [CheckoutController::class, 'failure'])->name('checkout.failure');

Route::post('/payments/{provider}/webhook', PaymentWebhookController::class)->name('payments.webhook');

Route::get('/admin/{path?}', function (?string $path = null) {
    $base = trim(config('freedom.admin_path'), '/');
    $target = $path ? "{$base}/{$path}" : $base;

    return redirect('/'.$target, 301);
})->where('path', '.*');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
