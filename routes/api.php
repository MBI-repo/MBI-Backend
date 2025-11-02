<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\WaitingListController;
use App\Models\WaitingList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::group(['prefix' => 'mbi'], function() {
    Route::post('contact-us/save', [HomepageController::class,'saveContact'])->name('saveContact');
    Route::get('contact-us/fetch/{id}', [HomepageController::class,'fetchC ontact'])->name('fetchContact');
    Route::get('contact-us/fetchall', [HomepageController::class,'fetchAllContact'])->name('fetchAllContact');
    
    Route::post('newsletter/save', [HomepageController::class,'saveNewsletter'])->name('saveNewsletter');
    Route::get('newsletter/fetch/{id}', [HomepageController::class,'fetchNewsletter'])->name('fetchNewsletter');
    Route::get('newsletter/fetchall', [HomepageController::class,'fetchAllNewsletter'])->name('fetchAllNewsletter');

    Route::post('waiting-list/save', [HomepageController::class,'saveWaitingList'])->name('saveWaitingList');
    Route::get('waiting-list/fetch/{id}', [HomepageController::class,'fetchWaitingList'])->name('fetchWaitingList');
    Route::get('waiting-list/fetchall', [HomepageController::class,'fetchAllWaitingList'])->name('fetchAllWaitingList');
});



// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
});