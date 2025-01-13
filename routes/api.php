<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\AuthTaskController;
use App\Http\Controllers\AuthController;

Route::post('/tasks', [TaskController::class, 'store']);
Route::get('/tasks', [TaskController::class, 'index']);
Route::get('/tasks/{id}', [TaskController::class, 'show']);
Route::put('/tasks/{id}', [TaskController::class, 'update']);
Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);
Route::delete('/tasks', [TaskController::class, 'destroyAll']);

Route::middleware(['throttle:6,1'])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('v2')->group(function () {
        Route::get('/tasks', [AuthTaskController::class, 'index']);
        Route::post('/tasks', [AuthTaskController::class, 'store']);
        Route::get('/tasks/{id}', [AuthTaskController::class, 'show']);
        Route::put('/tasks/{id}', [AuthTaskController::class, 'update']);
        Route::delete('/tasks/{id}', [AuthTaskController::class, 'destroy']);
        Route::delete('/tasks', [AuthTaskController::class, 'destroyAll']);
    });
    Route::post('/logout', [AuthController::class, 'logout']);
});
