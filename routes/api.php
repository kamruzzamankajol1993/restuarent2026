<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OfflinePos\OfflinePosDataController;
use App\Http\Middleware\VerifyOfflinePosKey;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('offline-pos')
    ->middleware(VerifyOfflinePosKey::class)
    ->group(function () {
        Route::get('/bootstrap', [OfflinePosDataController::class, 'bootstrap']);

        Route::get('/users', [OfflinePosDataController::class, 'users']);
        Route::get('/roles-permissions', [OfflinePosDataController::class, 'rolesPermissions']);

        Route::get('/restaurant-settings', [OfflinePosDataController::class, 'restaurantSettings']);
        Route::get('/pos-settings', [OfflinePosDataController::class, 'posSettings']);
        Route::get('/tax-settings', [OfflinePosDataController::class, 'taxSettings']);
        Route::get('/invoice-settings', [OfflinePosDataController::class, 'invoiceSettings']);

        Route::get('/zones', [OfflinePosDataController::class, 'zones']);
        Route::get('/tables', [OfflinePosDataController::class, 'tables']);
        Route::get('/waiters', [OfflinePosDataController::class, 'waiters']);
        Route::get('/customers', [OfflinePosDataController::class, 'customers']);

        Route::get('/food-categories', [OfflinePosDataController::class, 'foodCategories']);
        Route::get('/food-items', [OfflinePosDataController::class, 'foodItems']);
        Route::get('/food-addons', [OfflinePosDataController::class, 'foodAddons']);

        Route::get('/payment-methods', [OfflinePosDataController::class, 'paymentMethods']);
        Route::get('/active-orders', [OfflinePosDataController::class, 'activeOrders']);
        Route::get('/media-manifest', [OfflinePosDataController::class, 'mediaManifest']);
    });
