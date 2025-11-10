<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MessagesController extends Controller
{
    // POST /v1/messages
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'content' => ['nullable', 'string'],
            'conversationId' => ['required', 'integer', 'exists:conversations,id'],
            'messageType' => ['required', 'in:text,image,file,voicenote'],
            'fileData.url' => ['nullable', 'url'],
            'fileData.mimeType' => ['nullable', 'string'],
        ]);

        $conversation = Conversation::findOrFail($data['conversationId']);

        // Authorization: must be a participant
        if (!$conversation->participants()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // For non-text message types, ensure fileData exists
        if ($data['messageType'] !== 'text') {
            if (empty($data['fileData']['url']) || empty($data['fileData']['mimeType'])) {
                return response()->json(['message' => 'fileData.url and fileData.mimeType are required for non-text messages'], 422);
            }
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id, // ignore senderId from body, use auth user
            'message_type' => $data['messageType'],
            'content' => $data['content'] ?? null,
            'file_url' => $data['fileData']['url'] ?? null,
            'file_mime_type' => $data['fileData']['mimeType'] ?? null,
        ]);

        // Touch conversation updated_at
        $conversation->touch();

        return response()->json($this->formatMessage($message), 201);
    }

    // DELETE /v1/messages/{messageId}
    public function destroy($messageId, Request $request)
    {
        $user = $request->user();
        $message = Message::findOrFail($messageId);

        $conversation = $message->conversation;
        if (!$conversation->participants()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($message->sender_id !== $user->id) {
            return response()->json(['message' => 'Only sender can delete this message'], 403);
        }

        $message->delete();

        return response()->json([
            'messageId' => (string)$message->id,
            'status' => 'deleted',
        ]);
    }

    // POST /v1/messages/{messageId}/read
    public function markRead($messageId, Request $request)
    {
        $user = $request->user();
        $message = Message::findOrFail($messageId);
        $conversation = $message->conversation;

        if (!$conversation->participants()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Mark target message and all preceding messages (from other users) as read
        if ($message->sender_id !== $user->id) {
            $now = now();
            Message::where('conversation_id', $conversation->id)
                ->where('id', '<=', $message->id)
                ->whereNull('read_at')
                ->where('sender_id', '!=', $user->id)
                ->update(['read_at' => $now]);
        }

        return response()->json([
            'messageId' => (string)$message->id,
            'status' => 'read',
        ]);
    }

    private function formatMessage(Message $m): array
    {
        return [
            'id' => $m->id,
            'conversationId' => $m->conversation_id,
            'senderId' => $m->sender_id,
            'messageType' => $m->message_type,
            'content' => $m->content,
            'fileData' => $m->file_url ? [
                'url' => $m->file_url,
                'mimeType' => $m->file_mime_type,
            ] : null,
            'readAt' => $m->read_at,
            'createdAt' => $m->created_at,
        ];
    }
}