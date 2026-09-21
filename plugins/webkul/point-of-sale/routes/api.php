<?php

use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Webkul\PointOfSale\Http\Controllers\API\V1\BootController;
use Webkul\PointOfSale\Http\Controllers\API\V1\CatalogController;
use Webkul\PointOfSale\Http\Controllers\API\V1\OrderSyncController;
use Webkul\PointOfSale\Http\Controllers\API\V1\SessionController;

Route::name('admin.api.v1.point-of-sale.')->prefix('admin/api/v1/point-of-sale')->middleware([SubstituteBindings::class, 'auth:sanctum'])->group(function () {
    Route::post('orders/sync', [OrderSyncController::class, 'store'])->name('orders.sync');
    Route::get('configs/{config}/catalog', [CatalogController::class, 'show'])->name('configs.catalog');
    Route::get('sessions/{session}/boot', [BootController::class, 'show'])->name('sessions.boot');
    Route::get('configs/{config}/session', [SessionController::class, 'current'])->name('configs.session');
    Route::post('configs/{config}/session', [SessionController::class, 'open'])->name('configs.session.open');
    Route::post('sessions/{session}/opening-control', [SessionController::class, 'confirmOpeningControl'])->name('sessions.opening-control');
    Route::post('sessions/{session}/closing-control', [SessionController::class, 'requestClosing'])->name('sessions.closing-control');
    Route::post('sessions/{session}/close', [SessionController::class, 'close'])->name('sessions.close');
    Route::post('sessions/{session}/cash-movements', [SessionController::class, 'cashMovement'])->name('sessions.cash-movements');
});
