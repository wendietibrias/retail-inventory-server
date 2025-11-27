<?php

use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\CashierShiftController;
use App\Http\Controllers\V1\LeasingController;
use App\Http\Controllers\V1\NotificationController;
use App\Http\Controllers\V1\OperationalCostController;
use App\Http\Controllers\V1\PaymentMethodController;
use App\Http\Controllers\V1\PaymentTypeController;
use App\Http\Controllers\V1\PermissionController;
use App\Http\Controllers\V1\ReceiveableController;
use App\Http\Controllers\V1\ReceiveablePaymentController;
use App\Http\Controllers\V1\RoleController;
use App\Http\Controllers\V1\SalesInvoiceController;
use App\Http\Controllers\V1\SettingController;
use App\Http\Controllers\V1\ShiftTransactionController;
use App\Http\Controllers\V1\TransactionSummarizeController;
use App\Http\Controllers\V1\TransactionSummarizeDetailController;
use App\Http\Controllers\V1\UserController;
use App\Notifications\NotificationGatewayController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    Route::group(['prefix' => 'notification'], function () {
        Route::get('', [NotificationController::class, 'index']);
        Route::patch('{id}/read', [NotificationController::class, 'markAsRead']);
        Route::patch('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('{id}', [NotificationController::class, 'destroy']);
    });

    Route::group(['prefix' => 'leasing'], function () {
        Route::get('/', [LeasingController::class, 'index']);
        Route::post('', [LeasingController::class, 'create']);
        Route::patch('{id}', [LeasingController::class, 'update']);
        Route::get('{id}', [LeasingController::class, 'detail']);
        Route::delete('{id}', [LeasingController::class, 'delete']);
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
        Route::patch('{id}', [SalesInvoiceController::class, 'update']);
    });

    Route::group(['prefix' => 'cashier-shift'], function () {
        Route::get('', [CashierShiftController::class, 'index']);
        Route::post('', [CashierShiftController::class, 'create']);
        Route::get('{id}', [CashierShiftController::class, 'detail']);
    });
    Route::group(['prefix' => 'cashier-shift-detail'], function () { });
    Route::group(['prefix' => 'shift-transaction'], function () {
        Route::get('{id}', [ShiftTransactionController::class, 'indexByCashierShiftDetailId']);
        Route::post('{id}', [ShiftTransactionController::class, 'create']);
    });
    Route::group(['prefix' => 'receiveable'], function () {
        Route::get('', [ReceiveableController::class, 'index']);
        Route::get('{id}', [ReceiveableController::class, 'detail']);
    });
    Route::group(['prefix' => 'receiveable-payment'], function () {
        Route::post('', [ReceiveablePaymentController::class, 'create']);
        Route::get('{id}', [ReceiveablePaymentController::class, 'indexByReceiveableId']);
    });
    Route::group(['prefix' => 'transaction-summarize'], function () {
        Route::get('', [TransactionSummarizeController::class, 'index']);
        Route::get('{id}', [TransactionSummarizeController::class, 'detail']);
    });
    Route::group(['prefix' => 'transaction-summarize-detail'], function () {
        Route::get('{id}', [TransactionSummarizeDetailController::class, 'indexByTransactionSummarizeDetailId']);
    });
    Route::group(['prefix' => 'operational-cost'], function () {
        Route::post('', [OperationalCostController::class, 'create']);
        Route::patch('{id}', [OperationalCostController::class, 'updateStatus']);
    });
    Route::group(['prefix' => 'cashier-summarize'], function () { });
});
