<?php

use Illuminate\Support\Facades\Route;
use Tipoff\Checkout\Http\Controllers\Api\CartApplyCodeController;
use Tipoff\Checkout\Http\Controllers\Api\CartController;
use Tipoff\Checkout\Http\Controllers\Api\CartItemController;
use Tipoff\Checkout\Http\Controllers\Api\CartPurchaseController;

Route::middleware(config('tipoff.api.middleware_group'))
    ->prefix(config('tipoff.api.uri_prefix'))
    ->group(function () {

    // PUBLIC ROUTES
    Route::get('cart', [CartController::class, 'show']);
    Route::delete('cart', [CartController::class, 'destroy']);

    // PROTECTED ROUTES
    Route::middleware(config('tipoff.api.auth_middleware'))->group(function () {
        Route::resource('cart-items', CartItemController::class);
        Route::post('cart/apply-code', CartApplyCodeController::class);
        Route::post('cart/purchase', CartPurchaseController::class);
    });
});
