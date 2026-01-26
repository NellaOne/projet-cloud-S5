<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoadController;

Route::prefix('auth')->group(function () {
    Route::post('/signup', [AuthController::class, 'signup']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');
    Route::post('/unlock-account/{userId}', [AuthController::class, 'unlockAccount'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('roads', RoadController::class);
});

Route::get('/health', function () {
    return response()->json(['status' => 'OK']);
});
