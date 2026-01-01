<?php

use App\Http\Controllers\V1\Core\AuthController;
use App\Http\Controllers\V1\DashboardController;
use App\Http\Controllers\V1\Inventory\InventoryController;
use App\Http\Controllers\V1\Inventory\InventoryMovementController;
use App\Http\Controllers\V1\Inventory\MutationInController;
use App\Http\Controllers\V1\Inventory\MutatioOutnController;
use App\Http\Controllers\V1\Inventory\StockAdjustmentController;
use App\Http\Controllers\V1\MasterData\CustomerrController;
use App\Http\Controllers\V1\MasterData\ProductCategoryController;
use App\Http\Controllers\V1\MasterData\ProductController;
use App\Http\Controllers\V1\MasterData\ProductSkuSkuController;
use App\Http\Controllers\V1\MasterData\SupplierController;
use App\Http\Controllers\V1\MasterData\WarehouseController;
use App\Http\Controllers\V1\NotificationController;

use App\Http\Controllers\V1\PermissionController;

use App\Http\Controllers\V1\RoleController;
use App\Http\Controllers\V1\SettingController;
use App\Http\Controllers\V1\Transaction\InboundController;
use App\Http\Controllers\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('', [DashboardController::class, 'index']);
    });

    Route::group(['prefix' => 'notification'], function () {
        Route::get('', [NotificationController::class, 'index']);
        Route::patch('{id}/read', [NotificationController::class, 'markAsRead']);
        Route::patch('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('{id}', [NotificationController::class, 'destroy']);
    });


    Route::group(['prefix' => 'role'], function () {
        Route::get('', [RoleController::class, 'index']);
        Route::post('', [RoleController::class, 'create']);
        Route::patch('{id}', [RoleController::class, 'update']);
        Route::get('{id}', [RoleController::class, 'detail']);
        Route::delete('{id}', [RoleController::class, 'delete']);
    });

    Route::group(['prefix' => 'permission'], function () {
        Route::get('', [PermissionController::class, 'index']);
    });


    Route::group(['prefix' => 'setting'], function () {
        Route::get('', [SettingController::class, 'detail']);
        Route::patch('', [SettingController::class, 'update']);
    });

    Route::group(['prefix' => 'user'], function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('', [UserController::class, 'create']);
        Route::patch('{id}', [UserController::class, 'update']);
        Route::get('{id}', [UserController::class, 'detail']);
        Route::delete('{id}', [UserController::class, 'delete']);
    });

    Route::group(['prefix' => 'warehouse'], function () {
        Route::get('', [WarehouseController::class, 'index']);
        Route::post('', [WarehouseController::class, 'create']);
        Route::patch('{id}', [WarehouseController::class, 'update']);
        Route::delete('{id}', [WarehouseController::class, 'delete']);
    });

    Route::group(['prefix' => 'customer'], function () {
        Route::get('', [CustomerrController::class, 'index']);
        Route::post('', [CustomerrController::class, 'create']);
        Route::patch('{id}', [CustomerrController::class, 'update']);
        Route::delete('{id}', [CustomerrController::class, 'delete']);
    });

    Route::group(['prefix' => 'supplier'], function () {
        Route::get('', [SupplierController::class, 'index']);
        Route::post('', [SupplierController::class, 'create']);
        Route::patch('{id}', [SupplierController::class, 'update']);
        Route::delete('{id}', [SupplierController::class, 'delete']);
    });

    Route::group(['prefix' => 'product-category'], function () {
        Route::get('', [ProductCategoryController::class, 'index']);
        Route::post('', [ProductCategoryController::class, 'create']);
        Route::patch('{id}', [ProductCategoryController::class, 'update']);
        Route::delete('{id}', [ProductCategoryController::class, 'delete']);
    });

    Route::group(['prefix' => 'product'], function () {
        Route::get('', [ProductController::class, 'index']);
        Route::post('', [ProductController::class, 'create']);
        Route::patch('{id}', [ProductController::class, 'update']);
        Route::delete('{id}', [ProductController::class, 'delete']);
    });

    Route::group(['prefix' => 'product-sku'], function () {
        Route::get('', [ProductSkuSkuController::class, 'index']);
        Route::post('', [ProductSkuSkuController::class, 'create']);
        Route::post('{id}', [ProductSkuSkuController::class, 'update']);
        Route::delete('{id}', [ProductSkuSkuController::class, 'delete']);
    });

    Route::group(['prefix' => 'inbound'], function () {
        Route::get('', [InboundController::class, 'index']);
        Route::post('', [InboundController::class, 'create']);
        Route::patch('{id}', [InboundController::class, 'update']);
        Route::patch('{id}/status', [InboundController::class, 'changeStatus']);
        Route::get('{id}', [InboundController::class, 'detail']);

    });

    Route::group(['prefix' => 'inventory'], function () {
        Route::get('', [InventoryController::class, 'index']);
    });

    Route::group(['prefix' => 'inventory-movement'], function () {
        Route::get('', action: [InventoryMovementController::class, 'index']);
    });

    Route::group(['prefix' => 'mutation-in'], function () {
        Route::get('', [MutationInController::class, 'index']);
        Route::post('', [MutationInController::class, 'create']);
        Route::patch('{id}', [MutationInController::class, 'update']);
        Route::patch('{id}/status', [MutationInController::class, 'changeStatus']);
        Route::get('{id}', [MutationInController::class, 'detail']);
    });

    Route::group(['prefix' => 'mutation-out'], function () {
        Route::get('', [MutatioOutnController::class, 'create']);
        Route::patch('{id}', [MutatioOutnController::class, 'update']);
        Route::patch('{id}/status', [MutatioOutnController::class, 'changeStatus']);
        Route::get('{id}', [MutatioOutnController::class, 'detail']);
    });

    Route::group(['prefix' => 'stock-adjustment'], function () {
        Route::get('', [StockAdjustmentController::class, 'create']);
        Route::patch('{id}', [StockAdjustmentController::class, 'update']);
        Route::patch('{id}/status', [StockAdjustmentController::class, 'changeStatus']);
        Route::get('{id}', [StockAdjustmentController::class, 'detail']);
    });
});
