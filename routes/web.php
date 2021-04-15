<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tipoff\Checkout\Http\Controllers\CartController;
use Tipoff\Checkout\Http\Controllers\CheckoutController;
use Tipoff\Checkout\Http\Controllers\OrderController;
use Tipoff\Checkout\Http\Middleware\CartAuthenticate;

Route::middleware(config('tipoff.web.middleware_group'))
    ->prefix(config('tipoff.web.uri_prefix'))
    ->group(function () {

        // PUBLIC
        Route::get('cart/create', function () {
            return view('checkout::create');
        })->name('checkout.cart-create');

        Route::post('cart/create', [CartController::class, 'create']);

        // PROTECTED ROUTES - any auth ('email' or 'web') with custom redirect
        Route::middleware(CartAuthenticate::class)->group(function () {
            Route::post('cart/delete-item', [CartController::class, 'deleteItem'])->name('checkout.cart-delete-item');
            Route::post('cart/add-code', [CartController::class, 'addCode'])->name('checkout.cart.add-code');
            Route::get('cart', [CartController::class, 'show'])->name('checkout.cart-show');

            Route::post('checkout/purchase', [CheckoutController::class, 'purchase'])->name('checkout.purchase');
            Route::get('checkout', [CheckoutController::class, 'show'])->name('checkout.show');
        });

        // PROTECTED ROUTES - 'web' auth only
        Route::middleware(config('tipoff.web.auth_middleware').':web')->group(function () {
            Route::get('checkout/confirmation', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');
            Route::get('orders/{order}', [OrderController::class, 'show'])->name('checkout.order-show');
            Route::get('orders', [OrderController::class, 'index'])->name('checkout.orders');
        });
    });
