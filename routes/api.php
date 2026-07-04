<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MotorcycleController;
use App\Http\Controllers\Api\TeamUserController;
use App\Http\Controllers\Api\WorkOrderController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('teams/{team}')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'show']);
        Route::get('/branches', [BranchController::class, 'index']);
        Route::get('/brands', [CatalogController::class, 'brands']);
        Route::get('/motorcycle-models', [CatalogController::class, 'motorcycleModels']);
        Route::get('/maintenance-types', [CatalogController::class, 'maintenanceTypes']);
        Route::get('/users', [TeamUserController::class, 'index']);
        Route::get('/mechanics', [TeamUserController::class, 'mechanics']);

        Route::apiResource('clients', ClientController::class)->only(['index', 'store', 'show', 'update']);
        Route::apiResource('motorcycles', MotorcycleController::class)->only(['index', 'store', 'show', 'update']);
        Route::patch('/work-orders/{workOrder}/status', [WorkOrderController::class, 'updateStatus']);
        Route::apiResource('work-orders', WorkOrderController::class)->only(['index', 'store', 'show', 'update']);
    });
});
