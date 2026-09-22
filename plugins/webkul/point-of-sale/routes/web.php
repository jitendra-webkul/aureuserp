<?php

use Filament\Http\Middleware\Authenticate;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Webkul\PointOfSale\Http\Controllers\API\V1\BootController;
use Webkul\PointOfSale\Http\Controllers\API\V1\OrderSyncController;
use Webkul\PointOfSale\Http\Controllers\API\V1\TerminalProductController;
use Webkul\PointOfSale\Http\Controllers\PwaController;

Route::name('point-of-sale.till.')
    ->prefix('pos/till')
    ->middleware(['web', Authenticate::class, SubstituteBindings::class])
    ->group(function (): void {
        Route::post('orders/sync', [OrderSyncController::class, 'store'])->name('orders.sync');
        Route::get('sessions/{session}/boot', [BootController::class, 'show'])->name('sessions.boot');
        Route::post('configs/{config}/products', [TerminalProductController::class, 'store'])->name('configs.products.store');
    });

Route::name('point-of-sale.pwa.')
    ->prefix('pos')
    ->middleware(['web', SubstituteBindings::class])
    ->group(function (): void {
        Route::get('service-worker.js', [PwaController::class, 'serviceWorker'])->name('service-worker');
        Route::get('manifest.webmanifest', [PwaController::class, 'manifest'])->name('manifest');
        Route::get('{config}/manifest.webmanifest', [PwaController::class, 'manifest'])->name('manifest.config');
    });
