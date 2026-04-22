<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConnectionsController;
use App\Http\Controllers\ConversationsController;
use App\Http\Controllers\DiscoverController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\EventsController;
use App\Http\Controllers\GroupsController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationSettingsController;
use App\Http\Controllers\ReceivedController;
use App\Http\Controllers\SentController;
use App\Http\Controllers\WaitingListController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResourcesController;
use App\Http\Controllers\LaboratoryController;
use App\Models\WaitingList;
use Illuminate\Http\Request;
use Illuminate\Broadcasting\BroadcastController;
use Illuminate\Support\Facades\Route;






Route::group(['prefix' => 'v1'], function () {
    // Public routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('resetpasswordfield');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});

Route::group(['prefix' => 'v1/user'], function () {
    Route::get('/', [AuthController::class, 'user'])->name('fetchUser');
    Route::post('/', [AuthController::class, 'update'])->middleware('auth:sanctum')->name('updateUser');
});

Route::middleware('auth:sanctum')->prefix('v1/settings')->as('settings.')->group(function () {
    Route::get('/account-info/show', [ProfileController::class, 'showAccount'])->name('account_show');
    Route::put('/account-info/update', [ProfileController::class, 'updateAccount'])->name('account_update');
    Route::any('/account-info/avatar/update', [ProfileController::class, 'updateAvatar'])->name('avatar_update');

    Route::get('/profile/show', [ProfileController::class, 'viewProfile'])->name('profile_show');
    Route::put('/profile/update', [ProfileController::class, 'updateProfile'])->name('profile_update');

    Route::get('/notification/show', [NotificationSettingsController::class, 'viewSettings'])->name('settings_show');
    Route::put('/notification/update', [NotificationSettingsController::class, 'updateSettings'])->name('settings_update');

    Route::delete('/delete', [ProfileController::class, 'destroy'])->name('account_delete');
});

Route::middleware('auth:sanctum')->prefix('v1/notification')->as('notification.')->group(function () {

    Route::get('/show', [NotificationController::class, 'index'])->name('notification_show');
    Route::patch('/read/{id}', [NotificationController::class, 'markAsRead'])->name('notification_read');
    Route::patch('/read-all', [NotificationController::class, 'markAllAsRead'])->name('notification_readAll');
    Route::delete('/delete/{id}', [NotificationController::class, 'destroy'])->name('notification_delete');
});

//Route::middleware('auth:sanctum')->prefix('notification')->as('notification.')->group(function () {
// Route::get('/view-notificationsettings/show', [NotificationController::class, 'viewSettings'])->name('settings_show');
// Route::put('/update-notificationsettings/update', [NotificationController::class, 'updateSettings'])->name('settings_update');  
// // Route::any('/avatar', [NotificationController::class, 'updateAvatar'])->name('avatar_update');
// Route::get('/view-profile', [NotificationController::class, 'viewProfile'])->name('profile_show');
// Route::put('/update-profile', [NotificationController::class, 'updateProfile'])->name('profile_update');


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
Route::middleware('auth:sanctum')->prefix('v1/network')->as('network.')->group(function () {
    Route::get('discover/view', [DiscoverController::class, 'view'])->name('view-all');
    Route::get('discover/show-profile/{user_id}', [DiscoverController::class, 'show'])->name('view-profile');
    Route::post('discover/connect/{user_id}', [DiscoverController::class, 'add'])->name('connect');

    Route::get('received/view', [ReceivedController::class, 'view'])->name('view-recevied');
    Route::put('received/accept/{connection_id}', [ReceivedController::class, 'accept'])->name('accept');
    Route::put('received/reject/{connection_id}', [ReceivedController::class, 'reject'])->name('reject');

    Route::get('sent/view', [SentController::class, 'view'])->name('view-sent');
    Route::delete('sent/cancel/{connection_id}', [SentController::class, 'cancel'])->name('cancel');

    Route::get('connections/view', [ConnectionsController::class, 'view'])->name('fetch-connections');
    Route::get('connections/message/{user_id}', [ConnectionsController::class, 'createMessage'])->name('create-message');


    Route::get('/', [NetworkController::class, 'index'])->name('index');
});






// Protected routes
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// v1 Events - public GET
Route::prefix('v1')->group(function () {
    // Route::get('/events', [EventsController::class, 'index']);
    Route::get('/events/{id}', [EventsController::class, 'show']);
    Route::any('/fetch-all-events', [EventsController::class, 'fetchAll']);

    // Public product browsing
    Route::get('/products', [ProductsController::class, 'index']);
    Route::get('/products/donation', [ProductsController::class, 'donationProduct']);
    Route::get('/products/{id}', [ProductsController::class, 'show']);
    
});


//this is the new resources endpoint
Route::prefix('v1/resources')->group(function () {
    Route::get('/journals', [ResourcesController::class, 'index']);
    Route::get('/journals/{id}', [ResourcesController::class, 'show']);
    Route::get('/articles', [ResourcesController::class, 'articlesIndex']);
    Route::get('/articles/{id}', [ResourcesController::class, 'articlesShow']);
    Route::get('/rd', [ResourcesController::class, 'rdIndex']);
    Route::get('/rd/{id}', [ResourcesController::class, 'rdShow']);
    Route::post('/track-download', [ResourcesController::class, 'trackDownload']);
});

Route::middleware('auth:sanctum')->prefix('v1/resources')->group(function () {
    Route::post('/journals', [ResourcesController::class, 'store']);
    Route::post('/journals/update/{id}', [ResourcesController::class, 'update']);
    Route::delete('/journals/{id}', [ResourcesController::class, 'destroy']);
    Route::post('/articles', [ResourcesController::class, 'articlesStore']);
    Route::post('/articles/update/{id}', [ResourcesController::class, 'articlesUpdate']);
    Route::delete('/articles/{id}', [ResourcesController::class, 'articlesDestroy']);
    Route::post('/rd', [ResourcesController::class, 'rdStore']);
    Route::post('/rd/update/{id}', [ResourcesController::class, 'rdUpdate']);
    Route::delete('/rd/{id}', [ResourcesController::class, 'rdDestroy']);
});


// v1 Messaging & Groups API
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Pusher/Echo private channel auth endpoints (aliases for Laravel broadcasting auth)
    // These resolve 404s when the frontend is configured to call `/api/v1/api/pusher/auth`
    // or `/api/v1/broadcasting/auth` for joining private/presence channels.
    Route::post('/api/pusher/auth', [BroadcastController::class, 'authenticate']);
    Route::post('/broadcasting/auth', [BroadcastController::class, 'authenticate']);

    // Conversations
    Route::post('/conversations/direct', [ConversationsController::class, 'startDirect']);
    Route::get('/conversations', [ConversationsController::class, 'index']);
    Route::get('/inbox', [ConversationsController::class, 'inbox']);
    Route::post('/conversations/messages', [ConversationsController::class, 'messages']);
    Route::any('/conversations/allmessages', [ConversationsController::class, 'messages']);
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
    Route::post('/events/subscribe/{id}', [EventsController::class, 'subscribe']);
    // Route::get('/events/{id}', [EventsController::class, 'show']);
    Route::get('/events/fetchAllEvents/{id}', [EventsController::class, 'fetchAll']);
    Route::delete('/events/{id}', [EventsController::class, 'destroy']);

    // Marketplace: seller docs
    Route::post('/marketplace/seller/docs', [MarketplaceController::class, 'updateSellerDocs']);
    Route::post('/marketplace/seller/kyc', [MarketplaceController::class, 'verifyKyc']);

    // Product management (seller only for write)
    Route::post('/products', [ProductsController::class, 'store']);
    Route::get('/products/my-products/{userId}', [ProductsController::class, 'userProducts']);
    Route::post('/products/draft-products/{Id}', [ProductsController::class, 'draftProduct']);

    // Bidding
    Route::post('/products/bid/{id}', [ProductsController::class, 'submitBid']);
    Route::get('/my-biddings', [ProductsController::class, 'myBiddings']);
    Route::get('/biddings/{bidId}', [ProductsController::class, 'showBid']);

    Route::match(['put', 'post'], '/products/{id}', [ProductsController::class, 'update']);
    Route::delete('/products/{id}', [ProductsController::class, 'destroy']);
    Route::delete('/products/images/{imageId}', [ProductsController::class, 'destroyImage']);

    // Laboratory
    Route::prefix('laboratory')->group(function () {
        Route::get('/orders', [LaboratoryController::class, 'index']);
        Route::post('/orders', [LaboratoryController::class, 'store']);
        Route::get('/orders/{id}', [LaboratoryController::class, 'show']);
        Route::put('/orders/{id}', [LaboratoryController::class, 'update']);
        Route::delete('/orders/{id}', [LaboratoryController::class, 'destroy']);
        Route::get('/categories', [LaboratoryController::class, 'categories']);
        Route::get('/centers', [LaboratoryController::class, 'labCenters']);

        // Equipment
        Route::get('/equipments', [LaboratoryController::class, 'equipmentIndex']);
        Route::post('/equipments', [LaboratoryController::class, 'equipmentStore']);
        Route::delete('/equipments/{id}', [LaboratoryController::class, 'equipmentDelete']);

        // Results
        Route::get('/results', [LaboratoryController::class, 'resultsIndex']);
        Route::post('/results', [LaboratoryController::class, 'resultsStore']);
        Route::get('/results/{id}', [LaboratoryController::class, 'resultsShow']);
    });
});
