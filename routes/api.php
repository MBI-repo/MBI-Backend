<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConnectionsController;
use App\Http\Controllers\ConversationsController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\EventsController;
use App\Http\Controllers\GroupsController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\ReceivedController;
use App\Http\Controllers\SentController;
use App\Http\Controllers\WaitingListController;
use App\Models\WaitingList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;






// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::group(['prefix' => 'user'], function () {
    Route::get('/', [AuthController::class, 'user'])->name('fetchUser');
});


Route::group(['prefix' => 'mbi'], function () {
    Route::post('contact-us/save', [HomepageController::class, 'saveContact'])->name('saveContact');
    Route::get('contact-us/fetch/{id}', [HomepageController::class, 'fetchC ontact'])->name('fetchContact');
    Route::get('contact-us/fetchall', [HomepageController::class, 'fetchAllContact'])->name('fetchAllContact');

    Route::post('newsletter/save', [HomepageController::class, 'saveNewsletter'])->name('saveNewsletter');
    Route::get('newsletter/fetch/{id}', [HomepageController::class, 'fetchNewsletter'])->name('fetchNewsletter');
    Route::get('newsletter/fetchall', [HomepageController::class, 'fetchAllNewsletter'])->name('fetchAllNewsletter');

    Route::post('waiting-list/save', [HomepageController::class, 'saveWaitingList'])->name('saveWaitingList');
    Route::get('waiting-list/fetch/{id}', [HomepageController::class, 'fetchWaitingList'])->name('fetchWaitingList');
    Route::get('waiting-list/fetchall', [HomepageController::class, 'fetchAllWaitingList'])->name('fetchAllWaitingList');
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

// v1 Events - public GET
Route::prefix('v1')->group(function () {
    // Route::get('/events', [EventsController::class, 'index']);
    Route::get('/events/{id}', [EventsController::class, 'show']);
    Route::get('/fetch-all-events', [EventsController::class, 'fetchAll']);
});

// v1 Messaging & Groups API
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Conversations
    Route::post('/conversations/direct', [ConversationsController::class, 'startDirect']);
    Route::get('/conversations', [ConversationsController::class, 'index']);
    Route::get('/all-inbox', [ConversationsController::class, 'inbox']);
    Route::post('/conversations/messages', [ConversationsController::class, 'messages']);
    Route::post('/conversations/files', [ConversationsController::class, 'files']);

    // Messages
    Route::post('/messages', [MessagesController::class, 'store']);
    Route::post('/messages/delete', [MessagesController::class, 'destroy']);
    Route::post('/messages/read', [MessagesController::class, 'markRead']);

    // Groups
    Route::post('/groups', [GroupsController::class, 'store']);
    Route::post('/groups/participants', [GroupsController::class, 'addParticipants']);
    Route::delete('/groups/{groupId}/participants/{userId}', [GroupsController::class, 'removeParticipant']);
    Route::post('/groups/leave', [GroupsController::class, 'leave']);
    Route::delete('/groups/{groupId}', [GroupsController::class, 'destroy']);
    Route::get('/groups/{groupId}/profile', [GroupsController::class, 'profile']);

    // Events - protected write
    Route::post('/events', [EventsController::class, 'store']);
    Route::put('/events/{id}', [EventsController::class, 'update']);
    // Route::get('/events/{id}', [EventsController::class, 'show']);
    Route::get('/events/fetchAllEvents/{id}', [EventsController::class, 'fetchAll']);
    Route::delete('/events/{id}', [EventsController::class, 'destroy']);
});
