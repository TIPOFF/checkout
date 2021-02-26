<?php

use Illuminate\Support\Facades\Route;
use Tipoff\Checkout\Http\Controllers\Api\CartController;
use Tipoff\Checkout\Http\Controllers\Api\CartItemController;

Route::middleware(config('tipoff.api.middleware_group'))
    ->prefix(config('tipoff.api.uri_prefix'))
    ->group(function () {

    // PUBLIC ROUTES
    Route::get('cart', [CartController::class, 'show']);
    Route::delete('cart', [CartController::class, 'destroy']);

    Route::resource('cart-items', CartItemController::class);

    // PROTECTED ROUTES
    Route::middleware(config('tipoff.api.auth_middleware'))->group(function () {

    });
});
