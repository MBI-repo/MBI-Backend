<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

// Expose broadcasting auth route for API clients using Sanctum
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Authorize subscription to private conversation channels by numeric ID or UUID
Broadcast::channel('conversation.{conversationKey}', function ($user, $conversationKey) {
    $query = Conversation::query();
    $query->when(is_numeric($conversationKey), fn ($q) => $q->where('id', (int)$conversationKey))
          ->when(!is_numeric($conversationKey), fn ($q) => $q->where('uuid', (string)$conversationKey));

    return $query->whereHas('participants', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        })
        ->exists();
});

// Authorize subscription to private inbox channels by numeric ID or UUID
Broadcast::channel('inbox.{userKey}', function ($user, $userKey) {
    return (is_numeric($userKey) && (int)$user->id === (int)$userKey)
        || (!is_numeric($userKey) && (string)$user->uuid === (string)$userKey);
});

// Authorize subscription to private telemedicine session channels by UUID
Broadcast::channel('session.{sessionUuid}', function ($user, $sessionUuid) {
    return \App\Models\TelemedicineSession::where('uuid', $sessionUuid)
        ->where(function ($query) use ($user) {
            $query->where('patient_id', $user->id)
                  ->orWhere('doctor_id', $user->id);
        })
        ->exists();
});