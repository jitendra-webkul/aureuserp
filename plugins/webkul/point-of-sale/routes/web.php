<?php

use Filament\Http\Middleware\Authenticate;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Webkul\PointOfSale\Http\Controllers\API\V1\BootController;
use Webkul\PointOfSale\Http\Controllers\API\V1\OrderSyncController;
use Webkul\PointOfSale\Http\Controllers\API\V1\TerminalProductController;

Route::name('point-of-sale.till.')
    ->prefix('pos/till')
    ->middleware(['web', Authenticate::class, SubstituteBindings::class])
    ->group(function (): void {
        Route::post('orders/sync', [OrderSyncController::class, 'store'])->name('orders.sync');
        Route::get('sessions/{session}/boot', [BootController::class, 'show'])->name('sessions.boot');
        Route::post('configs/{config}/products', [TerminalProductController::class, 'store'])->name('configs.products.store');
    });
