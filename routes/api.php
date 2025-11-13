<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\WaitingListController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\ReceivedController;
use App\Http\Controllers\SentController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\ConnectionsController;
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
    Route::middleware('auth:sanctum')->prefix('network')->as('network.')->group(function () {
        // DISCOVER
        Route::prefix('discover')->controller(DiscoverController::class)->as('discover.')->group(function () {
            Route::get('view-all', 'index')->name('view-all');
            Route::get('view-profile/{user_id}', 'show')->name('view-profile');
            Route::post('connect/{user_id}', 'add')->name('connect');
        });
        // RECEIVED INVITATIONS
        Route::prefix('received')->controller(ReceivedController::class)->as('received.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::put('accept/{connection_id}', 'accept')->name('accept');
            Route::put('reject/{connection_id}', 'reject')->name('reject');
        });
        // SENT INVITATIONS
        Route::prefix('sent')->controller(SentController::class)->as('sent.')->group(function () {
            Route::get('/', 'index')->name('fetch');
            Route::delete('cancel/{connection_id}', 'cancel')->name('cancel');
        });
        // MY CONNECTIONS
        Route::prefix('connections')->controller(ConnectionsController::class)->as('connections.')->group(function () {
            Route::get('/', 'fetch')->name('fetch-connections');
            Route::get('message/{user_id}', 'create')->name('create-message');
        });
         
        Route::get('/', [NetworkController::class, 'index'])->name('index');
});



    


// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
});