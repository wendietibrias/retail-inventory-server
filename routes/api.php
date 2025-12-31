<?php

use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\DashboardController;
use App\Http\Controllers\V1\NotificationController;
use App\Http\Controllers\V1\PaymentMethodController;
use App\Http\Controllers\V1\PaymentTypeController;
use App\Http\Controllers\V1\PermissionController;
use App\Http\Controllers\V1\ReceiveableController;
use App\Http\Controllers\V1\ReceiveablePaymentController;
use App\Http\Controllers\V1\RoleController;
use App\Http\Controllers\V1\SalesInvoiceController;
use App\Http\Controllers\V1\SalesInvoiceDetailController;
use App\Http\Controllers\V1\SettingController;
use App\Http\Controllers\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    Route::group(['prefix'=>'dashboard'], function(){
        Route::get('', [DashboardController::class,'index']);
    });

    Route::group(['prefix' => 'notification'], function () {
        Route::get('', [NotificationController::class, 'index']);
        Route::patch('{id}/read', [NotificationController::class, 'markAsRead']);
        Route::patch('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('{id}', [NotificationController::class, 'destroy']);
    });

    Route::group(['prefix' => 'payment-method'], function () {
        Route::get('/', [PaymentMethodController::class, 'index']);
        Route::post('', [PaymentMethodController::class, 'create']);
        Route::patch('{id}', [PaymentMethodController::class, 'update']);
        Route::get('{id}', [PaymentMethodController::class, 'detail']);
        Route::delete('{id}', [PaymentMethodController::class, 'delete']);
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

    Route::group(['prefix' => 'payment-type'], function () {
        Route::get('/', [PaymentTypeController::class, 'index']);
        Route::post('', [PaymentTypeController::class, 'create']);
        Route::patch('{id}', [PaymentTypeController::class, 'update']);
        Route::get('{id}', [PaymentTypeController::class, 'detail']);
        Route::delete('{id}', [PaymentTypeController::class, 'delete']);
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

    Route::group(['prefix' => 'sales-invoice'], function () {
        Route::get('', [SalesInvoiceController::class, 'index']);
        Route::get('{id}', [SalesInvoiceController::class, 'detail']);
        Route::post('', [SalesInvoiceController::class, 'create']);
        Route::post('generate', [SalesInvoiceController::class, 'generate']);
        Route::post('generate-bulk', [SalesInvoiceController::class, 'generateBulk']);
        Route::delete('{id}', [SalesInvoiceController::class, 'delete']);
        Route::post('{id}', [SalesInvoiceController::class, 'update']);
        Route::patch('{id}/status', [SalesInvoiceController::class, 'changeStatus']);
    });

    Route::group(['prefix' => 'sales-invoice-detail'], function () {
        Route::get('groupped', [SalesInvoiceDetailController::class, 'grouppedSalesInvoiceDetail']);
    });

    Route::group(['prefix' => 'receiveable'], function () {
        Route::get('', [ReceiveableController::class, 'index']);
        Route::get('{id}', [ReceiveableController::class, 'detail']);
    });
    Route::group(['prefix' => 'receiveable-payment'], function () {
        Route::post('', [ReceiveablePaymentController::class, 'create']);
        Route::get('{id}', [ReceiveablePaymentController::class, 'indexByReceiveableId']);
        Route::patch('{id}/status', [ReceiveablePaymentController::class, 'changeStatus']);
    });
});
