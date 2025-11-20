<?php

use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\LeasingController;
use App\Http\Controllers\V1\PaymentMethodController;
use App\Http\Controllers\V1\PaymentTypeController;
use App\Http\Controllers\V1\PermissionController;
use App\Http\Controllers\V1\RoleController;
use App\Http\Controllers\V1\SettingController;
use App\Http\Controllers\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    Route::group(['prefix' => 'leasing'], function () {
        Route::get('/', [LeasingController::class, 'index']);
        Route::post('', [LeasingController::class, 'create']);
        Route::patch('{id}', [LeasingController::class, 'update']);
        Route::get('{id}', [LeasingController::class, 'detail']);
    });

    Route::group(['prefix' => 'payment-method'], function () {
        Route::get('/', [PaymentMethodController::class, 'index']);
        Route::post('', [PaymentMethodController::class, 'create']);
        Route::patch('{id}', [PaymentMethodController::class, 'update']);
        Route::get('{id}', [PaymentMethodController::class, 'detail']);
    });

    Route::group(['prefix' => 'role'], function () {
        Route::get('', [RoleController::class, 'index']);
        Route::post('', [RoleController::class, 'create']);
        Route::patch('{id}', [RoleController::class, 'update']);
        Route::get('{id}', [RoleController::class, 'detail']);
        Route::delete('{id}', [RoleController::class, 'delete']);
    });

    Route::group(['prefix'=>'permission'], function(){
        Route::get('', [PermissionController::class,'index']);
    });

    Route::group(['prefix' => 'payment-type'], function () {
        Route::get('/', [PaymentTypeController::class, 'index']);
        Route::post('', [PaymentTypeController::class, 'create']);
        Route::patch('{id}', [PaymentTypeController::class, 'update']);
        Route::get('{id}', [PaymentTypeController::class, 'detail']);
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
    });
});
