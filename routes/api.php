<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WaitingListController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/waiting-list', [WaitingListController::class, 'store']);
Route::get('/waiting-list', [WaitingListController::class, 'index']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
});