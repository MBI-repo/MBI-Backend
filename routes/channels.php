<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

// Expose broadcasting auth route for API clients using Sanctum
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Authorize subscription to private conversation channels
Broadcast::channel('conversation.{conversationId}', function ($user, int $conversationId) {
    return Conversation::query()
        ->where('id', $conversationId)
        ->whereHas('participants', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        })
        ->exists();
});